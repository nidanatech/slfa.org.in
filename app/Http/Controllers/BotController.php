<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AiBot; // Ensure Model name matches

class BotController extends Controller
{
    public function index()
    {
        return Inertia::render('Bots/GettingStarted', [
            'hasBots' => \App\Models\AiBot::exists()
        ]);
    }

    public function conversationalAi()
    {
        return Inertia::render('Bots/ConversationalAi', [
             'bots' => \App\Models\AiBot::withCount('sessions')
                ->orderByDesc('created_at')
                ->get()
        ]);
    }

    public function knowledgeBase()
    {
        return Inertia::render('Bots/KnowledgeBase');
    }

    public function help()
    {
        return Inertia::render('Bots/Help');
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        
        return Inertia::render('Bots/Create', [
            'channels' => Auth::user()->tenant?->channels()->get() ?? [],
            'calendars' => \App\Models\Calendar::all(),
            'users' => \App\Models\User::all(),
            'custom_fields' => \App\Models\CustomField::all(),
            'tags' => \App\Models\Tag::all(),
            'all_bots' => \App\Models\AiBot::all(),
            'workflows' => [], // Workflow model not yet implemented
            'pipelines' => \App\Models\Pipeline::all(),
            'knowledge_bases' => \App\Models\KnowledgeBase::all(),
            'training_data' => [],
            'businessTimezone' => \App\Models\BusinessProfile::value('timezone') ?? 'UTC',
            'businessName' => \App\Models\BusinessProfile::value('friendly_name') ?? 'our business',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string',
            'prompt' => 'nullable|string',
            'tone' => 'nullable|string',
            'personality' => 'nullable|string',
            'intent' => 'nullable|string',
            'additional_context' => 'nullable|string',
            // Config fields
            'channels' => 'nullable|array',
            'sleep_when_messaged' => 'boolean',
            'default_language' => 'nullable|string',
            'timezone' => 'nullable|string',
            'fallback_message' => 'nullable|string',
            'confidence_threshold' => 'nullable|numeric',
            'handover_timeout_seconds' => 'nullable|integer',
            'max_turns_per_session' => 'nullable|integer',
            // Actions
            'actions' => 'nullable|array',
            'actions_config' => 'nullable|array',
            'config' => 'nullable|array',
            // Knowledge Bases
            'knowledge_bases' => 'nullable|array',
            'knowledge_bases.*' => 'exists:knowledge_bases,id',
            'is_primary' => 'nullable|boolean',
        ]);

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return back()->with('error', 'No tenant assigned.');
        }

        // Construct prompt from structured fields
        $prompt = $this->constructPrompt(
            $validated['tone'] ?? '',
            $validated['personality'],
            $validated['intent'],
            $validated['additional_context']
        );

        // Pack extra fields into config for lightweight storage/future proofing
        // But store primary config in dedicated columns
        $config = $validated['config'] ?? [];
        $config['tone'] = $validated['tone'] ?? '';
        $config['personality'] = $validated['personality'];
        $config['intent'] = $validated['intent'];
        $config['additional_context'] = $validated['additional_context'];
        $config['simple_actions_list'] = $validated['actions'] ?? []; // Keep a simple list reference if needed
        
        try {
            $bot = $tenant->bots()->create([
                'name' => $validated['name'],
                'model' => 'gpt-4o-mini',
                'prompt' => $prompt,
                'config' => $config,
                'is_active' => true,
                'supported_channels' => $validated['channels'] ?? [],
                'sleep_on_manual_message' => $validated['sleep_when_messaged'] ?? true,
                'default_language' => $validated['default_language'] ?? 'en',
                'timezone' => $validated['timezone'] ?? 'UTC',
                'fallback_message' => $validated['fallback_message'] ?? null,
                'confidence_threshold' => $validated['confidence_threshold'] ?? 0.7,
                'handover_timeout_seconds' => $validated['handover_timeout_seconds'] ?? 0,
                'max_turns_per_session' => $validated['max_turns_per_session'] ?? 20,
                'is_primary' => $validated['is_primary'] ?? false,
            ]);
            
            // Handle Actions creation
            $this->syncActions($bot, $validated['actions'] ?? [], $validated['actions_config'] ?? []);
            
            // Sync knowledge bases with tenant_id
            if (isset($validated['knowledge_bases'])) {
                $bot->knowledgeBases()->sync(
                    collect($validated['knowledge_bases'])
                        ->mapWithKeys(fn($kbId) => [$kbId => ['tenant_id' => Auth::user()->tenant_id]])
                        ->toArray()
                );
            }
            
            // Sync with OpenAI again to include Tool definitions from actions
            $bot->syncOpenAiAssistant(false); // isCreating = false, because bot exists now

        } catch (\Exception $e) {
            \Log::error("Bot Creation Failed: " . $e->getMessage());
            return back()->with('error', "Failed to create bot: " . $e->getMessage());
        }

        return redirect()->route('bots.edit', $bot->id)->with('success', 'Bot created successfully. You can now configure training and goals.'); 
    }

    public function edit(Request $request, AiBot $bot)
    {
        if ($bot->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $bot->load(['actions.scenarios.sequences']);

        $tenantId = Auth::user()->tenant_id;
        
        // Get range parameter (default to 30 days)
        $range = $request->input('range', '30d');
        
        // Calculate date range
        $startDate = match($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'all' => null,
            default => now()->subDays(30),
        };
        
        // Calculate Analytics
        $botSessions = $bot->sessions()->with('conversation.channel')->get();
        $conversationIds = $botSessions->pluck('conversation_id')->unique();
        
        // Apply date filter if not "all time"
        if ($startDate) {
            $filteredConversationIds = \App\Models\Conversation::whereIn('id', $conversationIds)
                ->where('created_at', '>=', $startDate)
                ->pluck('id');
        } else {
            $filteredConversationIds = $conversationIds;
        }
        
        // Total conversations handled by this bot (within range)
        $totalConversations = $filteredConversationIds->count();
        
        // Conversations by channel
        $conversationsByChannel = \App\Models\Conversation::whereIn('id', $filteredConversationIds)
            ->with('channel')
            ->get()
            ->groupBy('channel.type')
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'channel_name' => $group->first()->channel->name ?? $group->first()->channel->type ?? 'Unknown'
                ];
            });
        
        // Total messages sent by bot
        $totalMessages = \App\Models\Message::whereIn('conversation_id', $filteredConversationIds)
            ->where('direction', 'outbound')
            ->where('sender_type', '!=', 'user') // Exclude manual agent messages
            ->count();
        
        // Average messages per conversation
        $avgMessagesPerConversation = $totalConversations > 0 ? round($totalMessages / $totalConversations, 1) : 0;
        
        // Active conversations (last 7 days)
        $activeConversations = \App\Models\Conversation::whereIn('id', $filteredConversationIds)
            ->where('last_message_at', '>=', now()->subDays(7))
            ->count();
        
        // Contacts created through this bot (within selected range)
        $contactsCreated = \App\Models\Conversation::whereIn('id', $filteredConversationIds)
            ->whereHas('contact', function($q) use ($startDate) {
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
            })
            ->count();
        
        // Appointments booked (if booking action is enabled)
        $appointmentsBooked = 0;
        if ($bot->actions()->where('action_type', 'book_appointment')->where('is_active', true)->exists()) {
            // Count events/appointments from conversations handled by this bot
            $appointmentsQuery = \App\Models\Event::whereIn('conversation_id', $filteredConversationIds);
            if ($startDate) {
                $appointmentsQuery->where('created_at', '>=', $startDate);
            }
            $appointmentsBooked = $appointmentsQuery->count();
        }
        
        // Handovers triggered
        $handoversQuery = \App\Models\BotSession::where('bot_id', $bot->id)
            ->whereNotNull('ended_at')
            ->where('ended_reason', 'handover');
        if ($startDate) {
            $handoversQuery->where('started_at', '>=', $startDate);
        }
        $handoversTriggered = $handoversQuery->count();
        
        // Recent activity (last 10 conversations)
        $recentActivity = \App\Models\Conversation::whereIn('id', $filteredConversationIds)
            ->with(['contact', 'channel'])
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($conv) {
                return [
                    'id' => $conv->id,
                    'contact_name' => $conv->contact->name ?? 'Unknown',
                    'channel' => $conv->channel->type ?? 'Unknown',
                    'last_message_at' => $conv->last_message_at,
                    'status' => $conv->status,
                ];
            });
        
        // Performance over time
        $dailyStatsQuery = \App\Models\Conversation::whereIn('id', $filteredConversationIds);
        if ($startDate) {
            $dailyStatsQuery->where('created_at', '>=', $startDate);
        }
        $dailyStats = $dailyStatsQuery
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->date => $item->count];
            });
        
        // Weekly traffic by weekday and channel
        $weeklyTraffic = \App\Models\Conversation::whereIn('id', $filteredConversationIds)
            ->with('channel')
            ->get()
            ->groupBy(function($conv) {
                return \Carbon\Carbon::parse($conv->created_at)->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
            })
            ->map(function($dayGroup) {
                // Group by channel within each day
                return $dayGroup->groupBy('channel.type')->map(function($channelGroup) {
                    return $channelGroup->count();
                });
            });
        
        // Format for frontend (convert day numbers to names and ensure all days exist)
        $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $formattedWeeklyTraffic = [];
        
        foreach ($weekDays as $index => $dayName) {
            $formattedWeeklyTraffic[$dayName] = $weeklyTraffic->get($index, collect())->toArray();
        }
        
        return Inertia::render('Bots/Create', [
            'bot' => $bot,
            'channels' => Auth::user()->tenant?->channels()->get() ?? [],
            'calendars' => \App\Models\Calendar::all(),
            'users' => \App\Models\User::where('tenant_id', $tenantId)->get(),
            'custom_fields' => \App\Models\CustomField::all(),
            'tags' => \App\Models\Tag::all(),
            'all_bots' => \App\Models\AiBot::where('id', '!=', $bot->id)->get(),
            'workflows' => [],
            'pipelines' => \App\Models\Pipeline::all(),
            'knowledge_bases' => \App\Models\KnowledgeBase::all(),
            'training_data' => \App\Models\BotTrainingData::where('bot_id', $bot->id)->orderBy('created_at', 'desc')->get(),
            'bot_knowledge_bases' => $bot->knowledgeBases()->pluck('knowledge_bases.id')->toArray(),
            'businessTimezone' => Auth::user()->tenant->timezone ?? 'UTC',
            // Analytics data
            'analytics' => [
                'total_conversations' => $totalConversations,
                'active_conversations' => $activeConversations,
                'total_messages' => $totalMessages,
                'avg_messages_per_conversation' => $avgMessagesPerConversation,
                'contacts_created' => $contactsCreated,
                'appointments_booked' => $appointmentsBooked,
                'handovers_triggered' => $handoversTriggered,
                'conversations_by_channel' => $conversationsByChannel,
                'recent_activity' => $recentActivity,
                'daily_stats' => $dailyStats,
                'weekly_traffic' => $formattedWeeklyTraffic,
                'weekly_traffic' => $formattedWeeklyTraffic,
            ],
            'businessName' => \App\Models\BusinessProfile::where('tenant_id', $tenantId)->value('friendly_name') ?? 'our business',
        ]);
    }

    public function update(Request $request, AiBot $bot)
    {
        if ($bot->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        \Log::info('Bot update request', [
            'bot_id' => $bot->id,
            'knowledge_bases' => $request->input('knowledge_bases'),
            'all_input' => $request->all()
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string',
            'prompt' => 'nullable|string',
            'tone' => 'nullable|string',
            'personality' => 'nullable|string',
            'intent' => 'nullable|string',
            'additional_context' => 'nullable|string',
            'channels' => 'nullable|array',
            'sleep_when_messaged' => 'boolean',
            'default_language' => 'nullable|string',
            'timezone' => 'nullable|string',
            'fallback_message' => 'nullable|string',
            'confidence_threshold' => 'nullable|numeric',
            'handover_timeout_seconds' => 'nullable|integer',
            'max_turns_per_session' => 'nullable|integer',
            'actions' => 'nullable|array',
            'actions_config' => 'nullable|array',
            'config' => 'nullable|array',
            // Knowledge Bases
            'knowledge_bases' => 'nullable|array',
            'knowledge_bases.*' => 'exists:knowledge_bases,id',
            'is_primary' => 'nullable|boolean',
        ]);

        // Construct prompt from structured fields
        $prompt = $this->constructPrompt(
            $validated['tone'] ?? '',
            $validated['personality'],
            $validated['intent'],
            $validated['additional_context']
        );

        // Store structured fields in config
        $config = $bot->config ?? [];
        $config['tone'] = $validated['tone'] ?? '';
        $config['personality'] = $validated['personality'];
        $config['intent'] = $validated['intent'];
        $config['additional_context'] = $validated['additional_context'];
        $config['simple_actions_list'] = $validated['actions'] ?? [];
        
        try {
            // Update dedicated columns
            $bot->update([
                'name' => $validated['name'],
                'model' => 'gpt-4o-mini',
                'prompt' => $prompt,
                'config' => $config,
                'supported_channels' => $validated['channels'] ?? [],
                'sleep_on_manual_message' => $validated['sleep_when_messaged'] ?? true,
                'default_language' => $validated['default_language'] ?? 'en',
                'timezone' => $validated['timezone'] ?? 'UTC',
                'fallback_message' => $validated['fallback_message'] ?? null,
                'confidence_threshold' => $validated['confidence_threshold'] ?? 0.7,
                'handover_timeout_seconds' => $validated['handover_timeout_seconds'] ?? 0,
                'max_turns_per_session' => $validated['max_turns_per_session'] ?? 20,
                'is_primary' => $validated['is_primary'] ?? false,
            ]);

            $bot->actions()->delete(); 
            $this->syncActions($bot, $validated['actions'] ?? [], $validated['actions_config'] ?? []);
            
            // Sync knowledge bases with tenant_id
            $bot->knowledgeBases()->sync(
                collect($validated['knowledge_bases'] ?? [])
                    ->mapWithKeys(fn($kbId) => [$kbId => ['tenant_id' => Auth::user()->tenant_id]])
                    ->toArray()
            );
            
            // Sync with OpenAI to update Tool definitions
            $bot->syncOpenAiAssistant(false);
            
        } catch (\Exception $e) {
             \Log::error("Bot Update Failed: " . $e->getMessage());
             return back()->with('error', "Failed to update bot: " . $e->getMessage());
        }

        return back()->with('success', 'Bot updated successfully.');
    }

    private function syncActions(AiBot $bot, array $actions, array $actionsConfigs)
    {
        if (empty($actions)) return;

        foreach ($actions as $actionType) {
            $actionConfig = $actionsConfigs[$actionType] ?? [];
            $actionName = $actionConfig['action_name'] ?? ucwords(str_replace('_', ' ', $actionType));
            
            $botAction = $bot->actions()->create([
                'tenant_id' => $bot->tenant_id,
                'action_type' => $actionType,
                'action_name' => $actionName,
                'is_active' => true,
                'config_json' => $actionConfig 
            ]);

            // Associated Config Table
            $botAction->actionConfig()->create([
                'tenant_id' => $bot->tenant_id,
                'config_json' => $actionConfig,
            ]);

            if (isset($actionConfig['scenarios']) && is_array($actionConfig['scenarios'])) {
                foreach ($actionConfig['scenarios'] as $scenarioData) {
                    $scenario = $botAction->scenarios()->create([
                        'tenant_id' => $bot->tenant_id,
                        'scenario_name' => $scenarioData['scenario_name'] ?? 'Default',
                        'trigger_condition' => $scenarioData['trigger_condition'] ?? 'phrase',
                        'trigger_config_json' => $scenarioData['trigger_config_json'] ?? [],
                        'final_message' => $scenarioData['final_message'] ?? null,
                        'assigned_user_id' => $scenarioData['assigned_user_id'] ?? null,
                        'target_bot_id' => $scenarioData['target_bot_id'] ?? null,
                        'create_task' => $scenarioData['create_task'] ?? false,
                        'add_contact_tag_id' => $scenarioData['add_contact_tag_id'] ?? null,
                        'reactivate_after_hours' => $scenarioData['reactivate_after_hours'] ?? 24,
                        'is_active' => true
                    ]);

                    if (isset($scenarioData['sequences']) && is_array($scenarioData['sequences'])) {
                        foreach ($scenarioData['sequences'] as $seqIndex => $seqData) {
                            $scenario->sequences()->create([
                                'tenant_id' => $bot->tenant_id,
                                'sequence_order' => $seqIndex + 1,
                                'delay_minutes' => $seqData['delay_minutes'] ?? 0,
                                'message_content' => $seqData['message_content'] ?? '',
                                'message_source' => $seqData['message_source'] ?? 'ai',
                                'is_active' => true
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Construct prompt from structured fields
     */
    private function constructPrompt(?string $tone, ?string $personality, ?string $intent, ?string $additionalContext): string
    {
        $parts = [];
        
        // 1. Strict Knowledge Base Instructions
        $parts[] = "### CRITICAL INSTRUCTIONS ###\n" .
                   "1. Use the provided Knowledge Base context to answer user questions.\n" .
                   "2. If the answer is not contained within the provided context, politely inform the user that you don't have that information. Do not invent answers or use outside knowledge for business-specific facts.\n" .
                   "3. Identify the core role and nature of the business from the context provided and act accordingly.";

        if (!empty($tone)) {
            $parts[] = "Tone: {$tone}";
        }
        
        if (!empty($personality)) {
            $parts[] = "Personality: {$personality}";
        }
        
        if (!empty($intent)) {
            $parts[] = "Intent: {$intent}";
        }
        
        if (!empty($additionalContext)) {
            $parts[] = "Additional Information & Business Context:\n{$additionalContext}";
        }
        
        return implode("\n\n", $parts);
    }

    public function destroy(AiBot $bot)
    {
        if ($bot->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        
        $bot->delete();
        return redirect()->route('bots.conversational_ai')->with('success', 'Agent deleted successfully.');
    }

    /**
     * Toggle global chatbot status for the tenant
     */
    public function toggleGlobalStatus(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $settings = $tenant->settings ?? [];
        
        $isEnabled = $request->boolean('enabled', true);
        $settings['is_chatbot_enabled'] = $isEnabled;
        
        $tenant->update(['settings' => $settings]);
        
        $status = $isEnabled ? 'enabled' : 'disabled';
        return back()->with('success', "Chatbot has been {$status} globally.");
    }
}
