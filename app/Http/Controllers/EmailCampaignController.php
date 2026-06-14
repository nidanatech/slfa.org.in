<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Models\CampaignRecipient;
use App\Models\EmailTemplate;
use App\Models\EmailAccount;
use App\Models\Contact;
use App\Models\EmailUnsubscribe;
use App\Jobs\SendCampaignJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::where('tenant_id', auth()->user()->tenant_id)
            ->with(['emailAccount', 'user'])
            ->latest()
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'subject' => $campaign->subject,
                    'status' => $campaign->status,
                    'campaign_type' => $campaign->campaign_type,
                    'total_recipients' => $campaign->total_recipients,
                    'sent_count' => $campaign->sent_count,
                    'open_rate' => $campaign->open_rate,
                    'click_rate' => $campaign->click_rate,
                    'scheduled_at' => $campaign->scheduled_at,
                    'created_at' => $campaign->created_at,
                    'email_account' => $campaign->emailAccount ? [
                        'email_address' => $campaign->emailAccount->email_address,
                        'provider' => $campaign->emailAccount->provider,
                    ] : null,
                ];
            });

        return Inertia::render('Marketing/EmailCampaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create()
    {
        $emailAccounts = EmailAccount::where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->get(['id', 'email_address', 'provider']);

        $templates = EmailTemplate::where('tenant_id', auth()->user()->tenant_id)
            ->orWhere('is_system', true)
            ->get(['id', 'name', 'category', 'thumbnail_url']);

        $customFields = \App\Models\CustomField::where('tenant_id', auth()->user()->tenant_id)
            ->get(['name', 'label'])
            ->toArray();

        return Inertia::render('Marketing/EmailCampaigns/Create', [
            'emailAccounts' => $emailAccounts,
            'templates' => $templates,
            'customFields' => $customFields,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email_account_id' => 'required|exists:email_accounts,id',
            'campaign_type' => 'required|in:newsletter,promotional,announcement',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string',
            'from_name' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'recipients' => 'required|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign = EmailCampaign::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'email_account_id' => $validated['email_account_id'],
            'name' => $validated['name'],
            'campaign_type' => $validated['campaign_type'],
            'subject' => $validated['subject'],
            'preheader' => $validated['preheader'] ?? null,
            'from_name' => $validated['from_name'] ?? null,
            'reply_to' => $validated['reply_to'] ?? null,
            'content_html' => $validated['content_html'],
            'content_text' => $validated['content_text'] ?? strip_tags($validated['content_html']),
            'template_id' => $validated['template_id'] ?? null,
            'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        // Add recipients (filter out unsubscribed)
        $tenantId = auth()->user()->tenant_id;
        $validRecipients = collect($validated['recipients'])->filter(function ($recipient) use ($tenantId) {
            return !EmailUnsubscribe::isUnsubscribed($tenantId, $recipient['email']);
        });

        foreach ($validRecipients as $recipient) {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
            ]);
        }

        $campaign->update(['total_recipients' => $validRecipients->count()]);

        return redirect()->route('marketing.email-campaigns.index')
            ->with('success', 'Campaign created successfully!');
    }

    public function show(EmailCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['emailAccount', 'recipients', 'clicks']);

        return Inertia::render('Marketing/EmailCampaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'campaign_type' => $campaign->campaign_type,
                'content_html' => $campaign->content_html,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'opened_count' => $campaign->opened_count,
                'clicked_count' => $campaign->clicked_count,
                'bounced_count' => $campaign->bounced_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'bounce_rate' => $campaign->bounce_rate,
                'scheduled_at' => $campaign->scheduled_at,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
                'created_at' => $campaign->created_at,
            ],
        ]);
    }

    public function send(EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->isDraft() && !$campaign->isScheduled()) {
            return back()->with('error', 'Campaign cannot be sent in current status.');
        }

        if ($campaign->total_recipients == 0) {
            return back()->with('error', 'Campaign has no recipients.');
        }

        // Dispatch job to send campaign
        SendCampaignJob::dispatch($campaign);

        return back()->with('success', 'Campaign is being sent!');
    }

    public function analytics(EmailCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $recipients = $campaign->recipients()
            ->with('clicks')
            ->get()
            ->map(function ($recipient) {
                return [
                    'email' => $recipient->email,
                    'name' => $recipient->name,
                    'status' => $recipient->status,
                    'sent_at' => $recipient->sent_at,
                    'opened_at' => $recipient->opened_at,
                    'clicked_at' => $recipient->clicked_at,
                    'opens_count' => $recipient->opens_count,
                    'clicks_count' => $recipient->clicks_count,
                    'clicks' => $recipient->clicks->map(fn($click) => [
                        'url' => $click->url,
                        'clicked_at' => $click->clicked_at,
                    ]),
                ];
            });

        // Top clicked links
        $topLinks = $campaign->clicks()
            ->select('url', \DB::raw('count(*) as clicks'))
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        return response()->json([
            'recipients' => $recipients,
            'top_links' => $topLinks,
            'stats' => [
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'opened_count' => $campaign->opened_count,
                'clicked_count' => $campaign->clicked_count,
                'bounced_count' => $campaign->bounced_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'bounce_rate' => $campaign->bounce_rate,
            ],
        ]);
    }

    public function destroy(EmailCampaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if ($campaign->isSending()) {
            return back()->with('error', 'Cannot delete campaign while sending.');
        }

        $campaign->delete();

        return redirect()->route('marketing.email-campaigns.index')
            ->with('success', 'Campaign deleted successfully!');
    }

    // Tracking endpoints
    public function trackOpen(Request $request, $campaignId, $recipientId)
    {
        $recipient = CampaignRecipient::where('campaign_id', $campaignId)
            ->where('id', $recipientId)
            ->first();

        if ($recipient) {
            $recipient->markAsOpened();
            $recipient->campaign->incrementOpenedCount();
        }

        // Return 1x1 transparent pixel
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'))
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function trackClick(Request $request, $campaignId, $recipientId)
    {
        $recipient = CampaignRecipient::where('campaign_id', $campaignId)
            ->where('id', $recipientId)
            ->first();

        $url = base64_decode($request->query('url'));

        if ($recipient && $url) {
            $recipient->markAsClicked();
            $recipient->campaign->incrementClickedCount();

            // Log the click
            \App\Models\CampaignClick::create([
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
                'url' => $url,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'clicked_at' => now(),
            ]);
        }

        // Redirect to original URL
        return redirect($url);
    }

    public function unsubscribe(Request $request, $campaignId, $recipientId)
    {
        $recipient = CampaignRecipient::where('campaign_id', $campaignId)
            ->where('id', $recipientId)
            ->first();

        if ($recipient) {
            EmailUnsubscribe::create([
                'tenant_id' => $recipient->campaign->tenant_id,
                'email' => $recipient->email,
                'campaign_id' => $campaignId,
                'reason' => $request->input('reason'),
                'unsubscribed_at' => now(),
            ]);

            return Inertia::render('Marketing/EmailCampaigns/Unsubscribed');
        }

        return redirect('/');
    }
}
