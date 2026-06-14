<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $filter = $request->input('filter', 'all'); // all, unread, starred, recent
        $scope = $request->input('scope', 'team'); // team, my
        $channelId = $request->input('channel_id');
        $search = $request->input('search');

        if (!$tenant) {
            return Inertia::render('Inbox/Index', ['conversations' => []]);
        }

        // Base Query
        $baseQuery = $tenant->conversations();

        // Scope Filter
        if ($scope === 'my') {
            $baseQuery->where('assigned_to', Auth::id());
        }

        // Channel Filter
        if ($channelId) {
            $baseQuery->where('channel_id', $channelId);
        }

        // Search Filter
        if ($search) {
            $baseQuery->where(function($q) use ($search) {
                $q->whereHas('contact', function($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })->orWhereHas('messages', function($mq) use ($search) {
                    $mq->where('content', 'like', "%{$search}%");
                });
            });
        }

        // Calculate Counts (Efficiently if possible, or simple separate queries)
        $counts = [
            'unread' => (clone $baseQuery)->where('is_read', false)->count(),
            'starred' => (clone $baseQuery)->where('is_important', true)->count(),
            'all' => (clone $baseQuery)->count(), 
            'assigned' => (clone $baseQuery)->where('assigned_to', Auth::id())->count(),
        ];
        $counts['recent'] = $counts['all'];


        // Filter Logic
        $query = $baseQuery->with(['contact.tags', 'channel' => function($query) {
            $query->withTrashed();
        }, 'assignee', 'tags', 'followers', 'latestMessage', 'activeBotSession.bot', 'referral'])
            ->orderByDesc('last_message_at');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter === 'starred') {
            $query->where('is_important', true);
        }
        // 'recent' and 'all' fall through to default list

        $conversations = $query->get();
        $allTags = $tenant->tags()->orderBy('name')->get();
        $teamMembers = $tenant->users()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);
        $channels = $tenant->channels()->get(['id', 'name', 'type', 'config'])
            ->filter(function ($channel) use ($tenant) {
                return $tenant->isChannelEnabled($channel->type);
            })
            ->map(function ($channel) {
                if ($channel->type === 'email' || $channel->type === 'gmail' || $channel->type === 'outlook' || $channel->type === 'ses') {
                    $accountId = $channel->config['email_account_id'] ?? null;
                    if ($accountId) {
                        $account = \App\Models\EmailAccount::find($accountId);
                        if ($account) {
                             $channel->account_status = $account->status;
                             
                             if ($account->provider === 'ses' && empty($account->imap_host)) {
                                 $channel->missing_configuration = true;
                             }
                        } else {
                            $channel->account_status = 'missing';
                        }
                    }
                }
                return $channel;
            })->values();

        return Inertia::render('Inbox/Index', [
            'conversations' => $conversations,
            'allTags' => $allTags,
            'teamMembers' => $teamMembers,
            'channels' => $channels,
            'filters' => [
                'current' => $filter,
                'scope' => $scope,
                'channel_id' => $channelId,
                'selected_id' => $request->input('selected_id'),
                'search' => $search,
                'counts' => $counts
            ]
        ]);
    }

    // ... (rest of methods)

    public function assign(Request $request, Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $conversation->update([
            'assigned_to' => $request->assigned_to
        ]);

        // Log/Activity stream?
        // Notification?

        return response()->json([
            'success' => true,
            'assigned_to' => $request->assigned_to
        ]);
    }

    public function show(Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // If it's an Inertia request, redirect to index with selected_id
        if (request()->header('X-Inertia')) {
            return redirect()->route('inbox.index', ['selected_id' => $conversation->id]);
        }

        $conversation->load([
            'messages', 
            'contact.notes.author', 
            'channel', 
            'assignee', 
            'tags', 
            'followers',
            'referral'
        ]);
        
        return response()->json($conversation);
    }
    
    // API endpoint for fetching messages only? 
    // Or we can return Inertia render with specific conversation selected.
    // Let's stick to returning JSON for dynamic loading or full page reload.
    // For React SPA feel, better to have a selectedConversation prop in Index.
    public function store(Request $request, Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $contact = $conversation->contact;
        if ($contact && $contact->isDndForChannel($conversation->channel?->type)) {
            return response()->json([
                'success' => false,
                'message' => 'Contact has Do Not Disturb enabled for this channel.'
            ], 422);
        }

        $message = $conversation->messages()->create([
            'direction' => 'outbound', // Agent reply
            'type' => 'text',
            'content' => $request->content,
            'sender_type' => 'user', 
        ]);
        
        $conversation->load('channel', 'contact');
        $channel = $conversation->channel;

        if ($channel) {
            try {
                $result = null;
                if (in_array($channel->type, ['email', 'gmail', 'outlook', 'ses'])) {
                    \App\Jobs\SendEmailReplyJob::dispatch($message);
                    $result = true; // Job dispatched
                } elseif ($channel->type === 'messenger') {
                    $service = new \App\Services\FacebookService($channel);
                    $result = $service->sendMessage($conversation->contact->external_id, $request->content);
                } elseif ($channel->type === 'instagram_message' || $channel->type === 'instagram') {
                    $service = new \App\Services\InstagramService($channel);
                    $result = $service->sendMessage($conversation->contact->external_id, $request->content);
                } elseif (in_array($channel->type, ['wordpress', 'web', 'api'])) {
                    $result = true; // Mark as sent immediately for polling/widget based channels
                }
                
                if (!$result) {
                    $message->update(['status' => 'failed']);
                    \Log::warning("Message delivery failed for channel {$channel->id} (Message ID: {$message->id})");
                } else {
                    $message->update(['status' => 'sent']);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send outbound message for channel {$channel->id}: " . $e->getMessage());
                $message->update(['status' => 'failed']);
            }
        }

        $conversation->touch('last_message_at');

        // Check if there's an active bot session and if bot should be paused
        if ($conversation->activeBotSession) {
            $bot = $conversation->activeBotSession->bot;
            
            if ($bot && $bot->sleep_on_manual_message) {
                // Pause the bot indefinitely until manually re-enabled
                // You can also set a time limit like: now()->addHours(24)
                $conversation->update([
                    'bot_disabled_until' => now()->addHours(24) // Pause for 24 hours
                ]);
                
                \Log::info("Bot {$bot->id} paused for conversation {$conversation->id} due to manual agent message");
            }
        }

        return response()->json($message);
    }

    /**
     * Resume bot for a conversation (clear bot_disabled_until)
     */
    public function resumeBot(Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Get the bot and session
        $botSession = $conversation->activeBotSession;
        if (!$botSession || !$botSession->bot) {
            return response()->json([
                'success' => false,
                'message' => 'No active bot session found'
            ], 400);
        }

        $bot = $botSession->bot;
        
        // Get messages since bot was paused
        $pausedAt = $conversation->bot_disabled_until ? 
            \Carbon\Carbon::parse($conversation->bot_disabled_until)->subYears(10) : // Subtract the 10 years we added
            $conversation->updated_at;
        
        $manualMessages = $conversation->messages()
            ->where('created_at', '>', $pausedAt)
            ->orderBy('created_at', 'asc')
            ->get();

        // Sync messages to OpenAI thread if there are any
        if ($manualMessages->count() > 0 && $botSession->thread_id) {
            try {
                $apiKey = $bot->tenant->openai_key ?? $bot->tenant->settings['openai_key'] ?? null;
                
                if ($apiKey) {
                    $headers = [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'OpenAI-Beta' => 'assistants=v2',
                        'Content-Type' => 'application/json',
                    ];
                    
                    $baseUrl = 'https://api.openai.com/v1';
                    $threadId = $botSession->thread_id;
                    
                    // Add a context message first
                    $contextMessage = "SYSTEM CONTEXT: The bot was paused while a human agent handled the conversation. Here is the conversation that occurred:\n\n";
                    
                    foreach ($manualMessages as $msg) {
                        $role = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
                        $contextMessage .= "{$role}: {$msg->content}\n";
                    }
                    
                    $contextMessage .= "\nThe bot is now resuming. Please acknowledge this context and continue assisting the customer naturally.";
                    
                    // Add the context message to the thread
                    \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->post("{$baseUrl}/threads/{$threadId}/messages", [
                            'role' => 'user',
                            'content' => $contextMessage,
                        ]);
                    
                    \Log::info("Synced {$manualMessages->count()} manual messages to thread for conversation {$conversation->id}");
                }
            } catch (\Exception $e) {
                \Log::error("Failed to sync messages to thread: " . $e->getMessage());
                // Don't fail the resume operation, just log the error
            }
        }

        // Clear the pause and reopen conversation if it was snoozed
        $updateData = ['bot_disabled_until' => null];
        
        // If conversation was snoozed, change status back to open
        if ($conversation->status === 'snoozed') {
            $updateData['status'] = 'open';
        }
        
        $conversation->update($updateData);

        \Log::info("Bot resumed for conversation {$conversation->id} by user " . Auth::id());

        return redirect()->back();
    }
    public function toggleStar(Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $newState = !$conversation->is_important;

        $conversation->update([
            'is_important' => $newState
        ]);

        // Sync to Email model
        if ($conversation->channel && in_array($conversation->channel->type, ['email', 'gmail', 'outlook', 'ses'])) {
            // Fetch meta from messages and extract email_id in PHP to avoid JSON query issues
            $emailIds = $conversation->messages()
                ->select('meta') // Optimize selection
                ->get()
                ->pluck('meta.email_id') // Collection pluck handles dot notation for arrays
                ->filter()
                ->toArray();
            
            if (!empty($emailIds)) {
                \App\Models\Email::whereIn('id', $emailIds)->update(['is_starred' => $newState]);
            }
        }
        
        // Sync to GMB? (If GMB has is_starred)
        // Assuming GMB might need similar logic if user complained about it.
        // But for now focusing on Email as confirmed schema exists.

        return redirect()->back();
    }

    public function stopBot(Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $conversation->update([
            'bot_disabled_until' => now()->addHours(24)
        ]);

        \Log::info("Bot manually stopped for conversation {$conversation->id} by user " . Auth::id());

        return redirect()->back();
    }

}
