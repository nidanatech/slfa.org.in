<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\StageChangeLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();

        // 1. Skip checks for System Admins
        if ($user->hasRole('system-admin')) {
             // Continue to dashboard
        } else {
            $tenant = $user->tenant;
            
            // 2. Check for Completed Onboarding
            if (!$tenant?->businessProfile) {
                return redirect()->route('onboarding.index');
            }

            // 3. Check for Active Subscription
            if (!$tenant || !$tenant->subscriptions()->where('stripe_status', 'active')->exists()) {
                return redirect()->route('subscription.select');
            }
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            // Fallback for users without tenant
            return Inertia::render('Dashboard', [
                'business_pulse' => $this->getEmptyBusinessPulse(),
                'smart_alerts' => [],
                'contextual_message' => "Welcome! Please complete your setup.",
                'contextual_actions' => [],
                'pipeline_snapshot' => null,
                'channel_stats' => [],
                'recent_activities' => [],
                'suggested_prompts' => $this->getSuggestedPrompts(),
            ]);
        }

        // Get Business Pulse KPIs
        $businessPulse = $this->getBusinessPulse($tenant);

        // Get Smart Alerts
        $smartAlerts = $this->getSmartAlerts($tenant);

        // Get Contextual Message
        $contextualMessage = $this->getContextualMessage($businessPulse, $user);

        // Get Contextual Actions
        $contextualActions = $this->getContextualActions($tenant, $businessPulse);

        // Get Pipeline Snapshot
        $pipelineSnapshot = $this->getPipelineSnapshot($tenant);

        // Get Channel Stats
        $channelStats = $this->getChannelStats($tenant);

        // Get Recent Activity
        $recentActivities = $this->getRecentActivity($tenant, 15);

        // Get Suggested Prompts
        $suggestedPrompts = $this->getSuggestedPrompts();

        // Get AI Service Metrics
        $aiServiceMetrics = $this->getAIServiceMetrics($tenant);

        return Inertia::render('Dashboard', [
            'business_pulse' => $businessPulse,
            'smart_alerts' => $smartAlerts,
            'contextual_message' => $contextualMessage,
            'contextual_actions' => $contextualActions,
            'pipeline_snapshot' => $pipelineSnapshot,
            'channel_stats' => $channelStats,
            'recent_activities' => $recentActivities,
            'suggested_prompts' => $suggestedPrompts,
            'ai_service_metrics' => $aiServiceMetrics,
            'is_chatbot_enabled' => $tenant->settings['is_chatbot_enabled'] ?? true,
        ]);
    }

    private function getBusinessPulse($tenant)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // New Leads Today
        $newLeadsToday = $tenant->contacts()
            ->where('created_at', '>=', $today)
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();
        
        $newLeadsYesterday = $tenant->contacts()
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();

        // Conversations Awaiting Reply
        $conversationsAwaitingReply = $tenant->conversations()
            ->where('status', 'open')
            ->whereHas('messages', function($q) {
                $q->where('direction', 'inbound')
                  ->where('created_at', '>', function($subQuery) {
                      $subQuery->selectRaw('COALESCE(MAX(created_at), ?)', ['1970-01-01'])
                               ->from('messages')
                               ->where('direction', 'outbound');
                  });
            })
            ->count();

        // Hot Leads (rating >= 4 OR high AI confidence)
        $hotLeads = $tenant->contacts()
            ->where(function($query) {
                $query->where('rating', '>=', 4)
                      ->orWhere('stage_confidence', '>=', 0.8);
            })
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();
            
        // Hot Leads Trend (vs Yesterday end of day) - Approximation for now by just checking created_at yesterday that are now hot? 
        // A better way is strictly new hot leads created today vs yesterday.
        $hotLeadsToday = $tenant->contacts()
             ->where('created_at', '>=', $today)
             ->where(function($query) {
                $query->where('rating', '>=', 4)
                      ->orWhere('stage_confidence', '>=', 0.8);
             })
             ->count();

        $hotLeadsYesterday = $tenant->contacts()
             ->where('created_at', '>=', $yesterday)
             ->where('created_at', '<', $today)
             ->where(function($query) {
                $query->where('rating', '>=', 4)
                      ->orWhere('stage_confidence', '>=', 0.8);
             })
             ->count();

        // Conversions This Week & Trend (vs Last Week)
        $closedWonStages = $tenant->pipelines()
            ->with('stages')
            ->get()
            ->flatMap->stages
            ->filter(function($stage) {
                return stripos($stage->name, 'closed') !== false || 
                       stripos($stage->name, 'won') !== false;
            })
            ->pluck('id');

        $conversionsThisWeek = $tenant->contacts()
            ->whereIn('stage_id', $closedWonStages)
            ->where('stage_updated_at', '>=', now()->startOfWeek())
            ->count();
            
        $conversionsLastWeek = $tenant->contacts()
            ->whereIn('stage_id', $closedWonStages)
            ->where('stage_updated_at', '>=', now()->subWeek()->startOfWeek())
            ->where('stage_updated_at', '<', now()->startOfWeek())
            ->count();

        // AI Stage Movements Today
        $aiStageMovementsToday = StageChangeLog::where('created_at', '>=', $today)
            ->where('triggered_by', 'ai')
            ->count();

        // Get AI Activity Breakdown
        $aiActivityBreakdown = $this->getAIActivityBreakdown($tenant, $today);

        // Average Response Time
        $avgResponseTime = $this->calculateAvgResponseTime($tenant);
        $hasResponseData = $avgResponseTime !== 'N/A';

        // Check for dependencies for smart zero-states
        $hasPipeline = $tenant->pipelines()->exists();
        $hasChannels = $tenant->channels()->where('is_active', true)->exists();

        // New requested metrics
        $totalLeads = $tenant->contacts()
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();

        $activeChannelsCount = $tenant->channels()->where('is_active', true)->count();
        $recentActivityCount = count($this->getRecentActivity($tenant, 50)); // Fetch enough to count today's activity or just use current session

        // AI Daily Summary logic
        $settings = $tenant->settings ?? [];
        $dailySummary = $settings['daily_pulse_summary'] ?? null;
        $todayStr = Carbon::today()->toDateString();
        
        if (!$dailySummary || ($dailySummary['date'] ?? '') !== $todayStr) {
            $dailySummaryContent = app(\App\Services\DashboardAssistantService::class)->generateDailySummary($tenant);
        } else {
            $dailySummaryContent = $dailySummary['content'];
        }

        return [
            'new_leads_today' => $newLeadsToday,
            'new_leads_trend' => $newLeadsToday - $newLeadsYesterday,
            'conversations_awaiting_reply' => $conversationsAwaitingReply,
            'hot_leads' => $hotLeads, // Keeping total for display, but could show "New Hot Leads" trend
            'hot_leads_trend' => $hotLeadsToday - $hotLeadsYesterday,
            'conversions_this_week' => $conversionsThisWeek,
            'conversions_trend' => $conversionsThisWeek - $conversionsLastWeek,
            'ai_stage_movements_today' => $aiStageMovementsToday,
            'ai_activity_breakdown' => $aiActivityBreakdown,
            'avg_response_time' => $avgResponseTime,
            'has_response_data' => $hasResponseData,
            'has_pipeline' => $hasPipeline,
            'has_channels' => $hasChannels,
            'total_leads' => $totalLeads,
            'active_channels_count' => $activeChannelsCount,
            'recent_activity_count' => $recentActivityCount,
            'daily_summary' => $dailySummaryContent,
        ];
    }

    public function regenerateSummary()
    {
        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return response()->json(['error' => 'No tenant found'], 404);
        }

        $summary = app(\App\Services\DashboardAssistantService::class)->generateDailySummary($tenant);

        return response()->json(['summary' => $summary]);
    }


    private function getSmartAlerts($tenant)
    {
        $alerts = [];

        // Hot leads not replied to in 2 hours
        $urgentLeads = $tenant->contacts()
            ->where('rating', '>=', 4)
            ->whereHas('conversations', function($q) {
                $q->where('last_message_at', '<', now()->subHours(2))
                  ->where('status', 'open');
            })
            ->count();

        if ($urgentLeads > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'message' => "{$urgentLeads} hot " . Str::plural('lead', $urgentLeads) . " need attention",
                'action' => 'view_hot_leads'
            ];
        }

        // Leads stuck in stage for 7+ days
        $stuckLeads = $tenant->contacts()
            ->where('stage_updated_at', '<', now()->subDays(7))
            ->whereNotNull('stage_id')
            ->count();

        if ($stuckLeads > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$stuckLeads} " . Str::plural('lead', $stuckLeads) . " inactive for 5+ days",
                'action' => 'review_stuck_leads'
            ];
        }

        return $alerts;
    }

    private function getContextualActions($tenant, $businessPulse)
    {
        $actions = [];

        // Priority 1: Conversations awaiting reply
        if ($businessPulse['conversations_awaiting_reply'] > 0) {
            $count = $businessPulse['conversations_awaiting_reply'];
            $actions[] = [
                'icon' => 'Inbox',
                'label' => "Reply to {$count} unread " . Str::plural('message', $count),
                'action' => 'inbox',
                'priority' => 'high'
            ];
        }

        // Priority 2: Hot leads
        if ($businessPulse['hot_leads'] > 0) {
            $count = $businessPulse['hot_leads'];
            $actions[] = [
                'icon' => 'Flame',
                'label' => "Review {$count} hot " . Str::plural('lead', $count),
                'action' => 'hot_leads',
                'priority' => 'high'
            ];
        }

        // Priority 3: AI-sorted leads
        if ($businessPulse['ai_stage_movements_today'] > 0) {
            $count = $businessPulse['ai_stage_movements_today'];
            $actions[] = [
                'icon' => 'Sparkles',
                'label' => "Review {$count} AI-sorted " . Str::plural('lead', $count),
                'action' => 'ai_sorted',
                'priority' => 'medium'
            ];
        }

        // Default actions when nothing urgent
        if (empty($actions)) {
            $actions = [
                ['icon' => 'PenTool', 'label' => 'Create a new social post', 'action' => 'social_planner', 'priority' => 'low'],
                ['icon' => 'BarChart3', 'label' => 'Check channel performance', 'action' => 'channels', 'priority' => 'low'],
                ['icon' => 'Telescope', 'label' => 'Analyze competitors', 'action' => 'competitors', 'priority' => 'low'],
            ];
        }

        return $actions;
    }

    private function getPipelineSnapshot($tenant)
    {
        $pipeline = $tenant->pipelines()->where('is_default', true)->first();
        
        if (!$pipeline) {
            $pipeline = $tenant->pipelines()->first();
        }

        if (!$pipeline) {
            return null;
        }

        $stages = $pipeline->stages()->orderBy('order_index')->get()->map(function($stage) {
            return [
                'name' => $stage->name,
                'count' => $stage->contacts()->count(),
                'color' => $stage->color ?? '#3b82f6'
            ];
        });

        $totalValue = $pipeline->opportunities()->sum('value_amount') ?? 0;

        return [
            'name' => $pipeline->name,
            'stages' => $stages,
            'total_value' => $totalValue,
        ];
    }

    private function getChannelStats($tenant)
    {
        $today = Carbon::today();

        return $tenant->channels()
            ->where('is_active', true)
            ->get()
            ->map(function($channel) use ($today) {
                $messagesCount = $channel->conversations()
                    ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                    ->where('messages.created_at', '>=', $today)
                    ->count();

                return [
                    'type' => $channel->type,
                    'name' => $channel->name,
                    'messages_today' => $messagesCount,
                    'new_leads' => $channel->contacts()
                        ->where('created_at', '>=', $today)
                        ->count(),
                    'hot_leads' => $channel->contacts()
                        ->where('rating', '>=', 4)
                        ->count(),
                ];
            })
            ->values();
    }

    private function getRecentActivity($tenant, $limit = 15)
    {
        $activities = collect();

        // Stage changes (last 24hrs)
        $stageChanges = StageChangeLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDay())
            ->with(['contact', 'newStage', 'newStage.pipeline'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function($log) {
                return [
                    'type' => 'stage_change',
                    'description' => ($log->contact ? $log->contact->name : 'Unknown Contact') . 
                                   " moved to \"" . ($log->newStage ? $log->newStage->name : 'Unknown Stage') . "\"" .
                                   ($log->triggered_by === 'ai' ? ' (AI)' : ''),
                    'actor' => ($log->triggered_by === 'ai') ? 'AI Agent' : 'User',
                    'timestamp' => $log->created_at,
                ];
            });

        $activities = $activities->merge($stageChanges);

        // New contacts
        $newContacts = $tenant->contacts()
            ->where('created_at', '>=', now()->subDay())
            ->with('channel')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function($contact) {
                $channelType = $contact->channel ? ucfirst($contact->channel->type) : 'Direct';
                return [
                    'type' => 'contact_created',
                    'description' => "New {$channelType} lead: {$contact->name}",
                    'actor' => 'System',
                    'timestamp' => $contact->created_at,
                ];
            });

        $activities = $activities->merge($newContacts);

        // New conversations
        $newConversations = $tenant->conversations()
            ->where('created_at', '>=', now()->subDay())
            ->with(['contact', 'channel'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function($conversation) {
                $channelType = $conversation->channel ? ucfirst($conversation->channel->type) : 'Unknown';
                $contactName = $conversation->contact ? $conversation->contact->name : 'Unknown Contact';
                return [
                    'type' => 'conversation_started',
                    'description' => "New {$channelType} conversation with {$contactName}",
                    'actor' => 'System',
                    'timestamp' => $conversation->created_at,
                ];
            });

        $activities = $activities->merge($newConversations);

        // Sort by timestamp and limit
        return $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->toArray();
    }

    private function getContextualMessage($businessPulse, $user)
    {
        $firstName = explode(' ', $user->name)[0];

        // Check for activity
        $hasActivity = $businessPulse['new_leads_today'] > 0 ||
                       $businessPulse['conversations_awaiting_reply'] > 0 ||
                       $businessPulse['hot_leads'] > 0;

        if (!$hasActivity) {
            return "Everything's quiet right now, {$firstName}. Want to generate new leads?";
        }

        $parts = [];
        if ($businessPulse['new_leads_today'] > 0) {
            $count = $businessPulse['new_leads_today'];
            $parts[] = "{$count} new " . Str::plural('lead', $count);
        }
        if ($businessPulse['conversations_awaiting_reply'] > 0) {
            $count = $businessPulse['conversations_awaiting_reply'];
            $parts[] = "{$count} " . Str::plural('conversation', $count) . " awaiting reply";
        }
        if ($businessPulse['hot_leads'] > 0) {
            $count = $businessPulse['hot_leads'];
            $parts[] = "{$count} hot " . Str::plural('lead', $count);
        }

        return "{$firstName}, " . implode(', ', $parts) . " today.";
    }

    private function calculateAvgResponseTime($tenant)
    {
        // Get pairs of inbound-outbound messages to calculate response times
        $conversations = $tenant->conversations()
            ->where('status', 'open')
            ->with(['messages' => function($query) {
                $query->where('created_at', '>=', now()->subDays(7))
                      ->orderBy('created_at');
            }])
            ->get();

        $responseTimes = [];

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;
            $lastInbound = null;

            foreach ($messages as $message) {
                if ($message->direction === 'inbound') {
                    $lastInbound = $message;
                } elseif ($message->direction === 'outbound' && $lastInbound) {
                    // Calculate time difference in seconds
                    $diff = $message->created_at->diffInSeconds($lastInbound->created_at);
                    $responseTimes[] = $diff;
                    $lastInbound = null; // Reset
                }
            }
        }

        if (empty($responseTimes)) {
            return 'N/A';
        }

        $avgSeconds = array_sum($responseTimes) / count($responseTimes);

        // Convert to human-readable format
        $hours = floor($avgSeconds / 3600);
        $minutes = floor(($avgSeconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m";
        } else {
            return "< 1m";
        }
    }

    private function getSuggestedPrompts()
    {
        return [
            [
                'icon' => 'Sparkles',
                'text' => "Summarize today's leads",
                'prompt' => 'Give me a summary of all leads created today with their sources'
            ],
            [
                'icon' => 'Target',
                'text' => 'Which leads are close to closing?',
                'prompt' => 'Show me leads in the final stage of the pipeline'
            ],
            [
                'icon' => 'Clock',
                'text' => 'Show contacts inactive for 7 days',
                'prompt' => "Find all contacts that haven't had activity in the last 7 days"
            ],
            [
                'icon' => 'TrendingUp',
                'text' => 'How many conversions this week?',
                'prompt' => 'Show me conversion statistics for this week'
            ],
            [
                'icon' => 'AlertTriangle',
                'text' => 'Leads needing attention',
                'prompt' => 'Show me high-value leads that need immediate follow-up'
            ],
        ];
    }

    private function getAIActivityBreakdown($tenant, $today)
    {
        $aiLogs = StageChangeLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $today)
            ->where('triggered_by', 'ai')
            ->with(['contact', 'newStage'])
            ->get();

        $breakdown = [];
        foreach ($aiLogs as $log) {
            if ($log->newStage && $log->contact) {
                $breakdown[] = [
                    'contact_name' => $log->contact->name,
                    'action' => "moved to " . $log->newStage->name,
                    'stage_name' => $log->newStage->name,
                ];
            }
        }

        return $breakdown;
    }

    private function getAIServiceMetrics($tenant)
    {
        $today = Carbon::today();
        
        $totalConversationsToday = $tenant->conversations()
            ->where('created_at', '>=', $today)
            ->count();
            
        $aiHandledConversations = $tenant->conversations()
            ->where('created_at', '>=', $today)
            ->whereDoesntHave('messages', function($query) {
                $query->where('sender_type', 'user');
            })
            ->whereHas('messages', function($query) {
                $query->where('sender_type', 'bot');
            })
            ->count();

        $automationRate = $totalConversationsToday > 0 
            ? round(($aiHandledConversations / $totalConversationsToday) * 100) 
            : 0;

        $tokenUsage = \App\Models\BotDailyTokenUsage::where('tenant_id', $tenant->id)
            ->where('date', $today->toDateString())
            ->sum('total_tokens');

        return [
            'automation_rate' => $automationRate,
            'ai_handled_count' => $aiHandledConversations,
            'total_conversations' => $totalConversationsToday,
            'token_usage' => $tokenUsage,
        ];
    }

    private function getEmptyBusinessPulse()
    {
        return [
            'new_leads_today' => 0,
            'new_leads_trend' => 0,
            'conversations_awaiting_reply' => 0,
            'hot_leads' => 0,
            'conversions_this_week' => 0,
            'ai_stage_movements_today' => 0,
            'ai_activity_breakdown' => [],
            'avg_response_time' => 'N/A',
            'has_response_data' => false,
            'total_leads' => 0,
            'active_channels_count' => 0,
            'recent_activity_count' => 0,
        ];
    }
}
