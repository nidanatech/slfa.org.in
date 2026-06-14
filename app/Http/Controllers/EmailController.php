<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Services\EmailWarmupService;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    protected $warmupService;
    protected $sesService;

    public function __construct(EmailWarmupService $warmupService, \App\Services\SesService $sesService)
    {
        $this->warmupService = $warmupService;
        $this->sesService = $sesService;
    }
    /**
     * List emails (unified inbox)
     */
    public function index(Request $request)
    {
        $query = Email::forTenant(auth()->user()->tenant_id)
            ->with(['emailAccount', 'attachments']);

        // Filter by account
        if ($request->has('account_id')) {
            $query->where('email_account_id', $request->account_id);
        }

        // Filter by direction
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter unread
        if ($request->boolean('unread')) {
            $query->unread();
        }

        // Filter starred
        if ($request->boolean('starred')) {
            $query->starred();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_address', 'like', "%{$search}%")
                  ->orWhere('body_text', 'like', "%{$search}%");
            });
        }

        $emails = $query->recent()
            ->paginate(50);

        return response()->json($emails);
    }

    /**
     * Get single email
     */
    public function show($emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)
            ->with(['emailAccount', 'attachments'])
            ->findOrFail($emailId);

        // Mark as read
        if (!$email->is_read) {
            $email->markAsRead();
        }

        return response()->json($email);
    }

    /**
     * Send new email
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:email_accounts,id',
            'to' => 'required|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'subject' => 'required|string',
            'body_text' => 'nullable|string',
            'body_html' => 'nullable|string',
        ]);

        $account = EmailAccount::forUser(auth()->id())
            ->findOrFail($validated['account_id']);

        if ($account->status !== 'active') {
            return response()->json(['error' => 'Email account is not active'], 400);
        }

        // Dispatch send job
        SendEmailJob::dispatch($account, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Email queued for sending',
        ]);
    }

    /**
     * Reply to email
     */
    public function reply(Request $request, $emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)->findOrFail($emailId);

        $validated = $request->validate([
            'body_text' => 'nullable|string',
            'body_html' => 'nullable|string',
        ]);

        $replyData = [
            'account_id' => $email->email_account_id,
            'to' => $email->from_address,
            'subject' => 'Re: ' . $email->subject,
            'body_text' => $validated['body_text'] ?? null,
            'body_html' => $validated['body_html'] ?? null,
        ];

        SendEmailJob::dispatch($email->emailAccount, $replyData);

        return response()->json([
            'success' => true,
            'message' => 'Reply queued for sending',
        ]);
    }

    /**
     * Forward email
     */
    public function forward(Request $request, $emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)->findOrFail($emailId);

        $validated = $request->validate([
            'to' => 'required|string',
            'body_text' => 'nullable|string',
            'body_html' => 'nullable|string',
        ]);

        $forwardData = [
            'account_id' => $email->email_account_id,
            'to' => $validated['to'],
            'subject' => 'Fwd: ' . $email->subject,
            'body_text' => ($validated['body_text'] ?? '') . "\n\n--- Forwarded message ---\n" . $email->body_text,
            'body_html' => ($validated['body_html'] ?? '') . '<br><br>--- Forwarded message ---<br>' . $email->body_html,
        ];

        SendEmailJob::dispatch($email->emailAccount, $forwardData);

        return response()->json([
            'success' => true,
            'message' => 'Email forwarded',
        ]);
    }

    /**
     * Mark as read/unread
     */
    public function markAsRead($emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)->findOrFail($emailId);
        $email->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Star/unstar email
     */
    public function toggleStar($emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)->findOrFail($emailId);
        $email->toggleStar();

        return response()->json([
            'success' => true,
            'is_starred' => $email->is_starred,
        ]);
    }

    /**
     * Delete email
     */
    public function destroy($emailId)
    {
        $email = Email::forTenant(auth()->user()->tenant_id)->findOrFail($emailId);
        $email->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get channel overview with metrics and account info
     */
    /**
     * Get channel overview with metrics and account info
     */
    public function getChannelOverview($channelId)
    {
        try {
            $channel = \App\Models\Channel::findOrFail($channelId);
            
            // Verify channel belongs to user's tenant
            if ($channel->tenant_id !== auth()->user()->tenant_id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get date range (last 30 days by default)
            $startDate = now()->subDays(30);
            $endDate = now();

            // Calculate metrics from conversations
            $metrics = $this->calculateChannelMetrics($channel, $startDate, $endDate);

            // Get account information
            $config = $channel->config ?? [];
            $accountInfo = [
                'id' => $config['email_account_id'] ?? null,
                'email' => $config['email'] ?? 'N/A',
                'provider' => $channel->type === 'gmail' ? 'Gmail' : ($channel->type === 'outlook' ? 'Outlook' : ($channel->type === 'ses' ? 'Custom Email' : 'Email')),
                'status' => $channel->is_active ? 'active' : 'inactive',
                'connected_at' => $channel->created_at->toISOString(),
                'last_synced' => $channel->updated_at->toISOString(),
            ];

            return response()->json([
                'account' => $accountInfo,
                'summary' => $metrics['summary'],
                'daily_metrics' => $metrics['daily'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Email Overview Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get channel metrics for a specific date range
     */
    public function getChannelMetrics(Request $request, $channelId)
    {
        try {
            $channel = \App\Models\Channel::findOrFail($channelId);
            
            // Verify channel belongs to user's tenant
            if ($channel->tenant_id !== auth()->user()->tenant_id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $days = $request->input('days', 30);
            $startDate = now()->subDays($days);
            $endDate = now();

            $metrics = $this->calculateChannelMetrics($channel, $startDate, $endDate);

            return response()->json($metrics);
        } catch (\Exception $e) {
            \Log::error('Email Metrics Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate metrics from conversations and messages
     */
    private function calculateChannelMetrics($channel, $startDate, $endDate)
    {
        // Get all conversations for this channel in the date range
        $conversations = \App\Models\Conversation::where('channel_id', $channel->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('messages')
            ->get();

        // Initialize daily metrics array
        $dailyMetrics = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dailyMetrics[$dateKey] = [
                'date' => $dateKey,
                'emails_sent' => 0,
                'emails_received' => 0,
                'emails_replied' => 0,
                'total_response_time_minutes' => 0,
                'response_count' => 0,
            ];
            $currentDate->addDay();
        }

        // Process conversations and messages
        foreach ($conversations as $conversation) {
            $messages = $conversation->messages()->orderBy('created_at')->get();
            
            foreach ($messages as $message) {
                $dateKey = $message->created_at->format('Y-m-d');
                
                if (!isset($dailyMetrics[$dateKey])) {
                    continue;
                }

                // Count sent vs received
                if ($message->direction === 'outbound') {
                    $dailyMetrics[$dateKey]['emails_sent']++;
                } else {
                    $dailyMetrics[$dateKey]['emails_received']++;
                }
            }

            // Calculate response time for conversations with replies
            if ($messages->count() >= 2) {
                $firstIncoming = $messages->where('direction', 'inbound')->first();
                $firstOutgoing = $messages->where('direction', 'outbound')
                    ->where('created_at', '>', optional($firstIncoming)->created_at)
                    ->first();

                if ($firstIncoming && $firstOutgoing) {
                    $responseTime = $firstIncoming->created_at->diffInMinutes($firstOutgoing->created_at);
                    $dateKey = $firstOutgoing->created_at->format('Y-m-d');
                    
                    if (isset($dailyMetrics[$dateKey])) {
                        $dailyMetrics[$dateKey]['total_response_time_minutes'] += $responseTime;
                        $dailyMetrics[$dateKey]['response_count']++;
                        $dailyMetrics[$dateKey]['emails_replied']++;
                    }
                }
            }
        }

        // Calculate averages and format daily metrics
        $formattedDaily = [];
        foreach ($dailyMetrics as $dateKey => $metrics) {
            $avgResponseTime = $metrics['response_count'] > 0
                ? round($metrics['total_response_time_minutes'] / $metrics['response_count'])
                : 0;

            $formattedDaily[] = [
                'metric_date' => $dateKey,
                'emails_sent' => $metrics['emails_sent'],
                'emails_received' => $metrics['emails_received'],
                'emails_replied' => $metrics['emails_replied'],
                'avg_response_time_minutes' => $avgResponseTime,
            ];
        }

        // Calculate summary statistics
        $totalSent = array_sum(array_column($dailyMetrics, 'emails_sent'));
        $totalReceived = array_sum(array_column($dailyMetrics, 'emails_received'));
        $totalReplied = array_sum(array_column($dailyMetrics, 'emails_replied'));
        $totalResponseTime = array_sum(array_column($dailyMetrics, 'total_response_time_minutes'));
        $totalResponseCount = array_sum(array_column($dailyMetrics, 'response_count'));

        $avgResponseTime = $totalResponseCount > 0
            ? round($totalResponseTime / $totalResponseCount)
            : 0;

        $summary = [
            'total_emails' => $totalSent + $totalReceived,
            'emails_sent' => $totalSent,
            'emails_received' => $totalReceived,
            'emails_replied' => $totalReplied,
            'avg_response_time_minutes' => $avgResponseTime,
            'response_rate' => $totalReceived > 0 
                ? round(($totalReplied / $totalReceived) * 100, 1)
                : 0,
        ];

        return [
            'summary' => $summary,
            'daily' => $formattedDaily,
        ];
    }

    /**
     * Get channel warmup stats
     */
    public function getChannelWarmup($channelId)
    {
        $channel = \App\Models\Channel::findOrFail($channelId);
        
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $config = $channel->config ?? [];
        $accountId = $config['email_account_id'] ?? null;

        if (!$accountId) {
             // Fallback: try to find email account by channel (if linked another way)
             // But usually it's in config for 'email' channels created via EmailAccountController
             return response()->json(['error' => 'Email account not linked'], 404);
        }

        $account = EmailAccount::forUser(auth()->id())->find($accountId);
        
        if (!$account) {
            return response()->json(['error' => 'Email account not found'], 404);
        }

        $stats = $this->warmupService->getWarmupStats($account);

        $recentEmails = Email::where('email_account_id', $account->id)
            ->where('is_warmup', true)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($email) {
                return [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'to' => $email->to_addresses[0] ?? 'Unknown',
                    'sent_at' => $email->created_at->diffForHumans(),
                    'status' => 'Sent'
                ];
            });

        $totalAccounts = EmailAccount::where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'account_id' => $account->id,
            'stats' => $stats,
            'total_accounts_count' => $totalAccounts,
            'schedule' => [
                'current_limit' => $stats['emails_today'],
            ],
            'recent_emails' => $recentEmails
        ]);
    }
    /**
     * Get deliverability stats for SES
     */
    public function getChannelDeliverability($channelId)
    {
        $channel = \App\Models\Channel::findOrFail($channelId);
        
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($channel->type !== 'ses') {
            return response()->json(['error' => 'Not supported for this channel type'], 400);
        }

        // Get SES stats
        $points = $this->sesService->getSendStatistics();

        // Format for chart (SES returns generic data points, usually every 15 mins for 2 weeks)
        // We aggregate by day for simplicity, or return as is.
        // DataPoint structure: ['Timestamp', 'DeliveryAttempts', 'Bounces', 'Complaints', 'Rejects']
        
        $formatted = collect($points)->map(function($point) {
            return [
                'timestamp' => \Carbon\Carbon::parse($point['Timestamp'])->toISOString(),
                'attempts' => $point['DeliveryAttempts'],
                'bounces' => $point['Bounces'],
                'complaints' => $point['Complaints'],
                'rejects' => $point['Rejects'],
            ];
        })->sortBy('timestamp')->values();

        // Calculate summary (current 24h window or total returned?)
        // SES 'GetSendStatistics' returns data for the last 2 weeks.
        
        $summary = [
            'total_attempts' => $formatted->sum('attempts'),
            'total_bounces' => $formatted->sum('bounces'),
            'total_complaints' => $formatted->sum('complaints'),
            'total_rejects' => $formatted->sum('rejects'),
        ];
        
        // Calculate rates
        if ($summary['total_attempts'] > 0) {
            $summary['bounce_rate'] = round(($summary['total_bounces'] / $summary['total_attempts']) * 100, 2);
            $summary['complaint_rate'] = round(($summary['total_complaints'] / $summary['total_attempts']) * 100, 2);
        } else {
            $summary['bounce_rate'] = 0;
            $summary['complaint_rate'] = 0;
        }

        return response()->json([
            'summary' => $summary,
            'points' => $formatted
        ]);
    }
}
