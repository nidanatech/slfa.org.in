<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Contact;
use App\Models\Task;
use App\Models\Note;
use App\Models\StageChangeLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardAssistantService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        // Try env, then fallback to first tenant if needed, but for "System Assistant" we should use env.
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function ensureAssistant(Tenant $tenant)
    {
        $apiKey = $tenant->openai_key ?? env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception("OpenAI API Key not configured for this tenant or system.");
        }

        $this->apiKey = $apiKey;

        if ($tenant->dashboard_assistant_id) {
            return $tenant->dashboard_assistant_id;
        }

        $profile = $tenant->businessProfile;
        $businessName = $profile?->business_name ?? $tenant->name;
        $location = $profile?->business_location ?? 'the cloud';
        $friendlyName = $profile?->friendly_business_name ?? $businessName;

        // Create Assistant
        $response = Http::withHeaders($this->getHeaders())->post("{$this->baseUrl}/assistants", [
            'model' => 'gpt-4o-mini',
            'name' => "Sydkic Assistant for {$businessName}",
            'instructions' => $this->getInstructions($tenant),
            'tools' => $this->getTools()
        ]);

        if ($response->successful()) {
            $id = $response->json()['id'];
            $tenant->update(['dashboard_assistant_id' => $id]);
            return $id;
        }

        throw new \Exception("Failed to create assistant: " . $response->body());
    }

    public function runTool(User $user, $functionName, $arguments)
    {
        $tenant = $user->tenant;
        if (!$tenant) return "No Tenant Context Found.";

        // Ensure API Key is set for the tool execution context (if not already via ensureAssistant)
        if (!$this->apiKey) {
            $this->apiKey = $tenant->openai_key ?? env('OPENAI_API_KEY');
        }

        switch ($functionName) {
            // CRM & Communication
            case 'search_contacts':
                return $this->searchContacts($tenant, $arguments['query'] ?? '');
            
            case 'search_conversations':
                return $this->searchConversations($tenant, $arguments['query'] ?? '');
                
            case 'get_contact_details':
                return $this->getContactDetails($tenant, $arguments['contact_id'] ?? null);

            case 'create_contact':
                return $this->createContact($tenant, $user, $arguments);

            case 'create_opportunity':
                return $this->createOpportunity($tenant, $user, $arguments);

            // Pipeline & Business Intelligence
            case 'get_pipeline_summary':
                return $this->getPipelineSummary($tenant);
                
            case 'get_leads_needing_attention':
                return $this->getLeadsNeedingAttention($tenant);
                
            case 'get_contacts_by_stage':
                return $this->getContactsByStage($tenant, $arguments['stage_id'] ?? null);

            case 'ai_bots_settings':
                return $this->getAiBotsSettings($tenant);

            case 'get_app_routes':
                return $this->getAppRoutes();

            case 'provide_url':
                return $this->provideUrl($arguments['page_name'] ?? '');

            // Tasks & Notes
            case 'create_task':
                return $this->createTask($tenant, $user, $arguments);
                
            case 'create_note':
                return $this->createNote($tenant, $user, $arguments);

            // Legacy Tools
            case 'get_active_channels':
                return $this->getActiveChannels($tenant);
                
            case 'get_unread_counts':
                return $this->getUnreadCounts($tenant);

            case 'analyze_pipeline_bottlenecks':
                return $this->analyzePipelineBottlenecks($tenant);

            case 'get_channel_performance':
                return $this->getChannelPerformance($tenant);
                
            case 'get_trending_channels':
                return $this->getTrendingChannels($tenant);

            case 'get_high_value_contacts':
                return $this->getHighValueContacts($tenant);
                
            case 'get_recent_contacts':
                return $this->getRecentContacts($tenant, $arguments['limit'] ?? 10);

            case 'get_overdue_tasks':
                return $this->getOverdueTasks($tenant);
                
            case 'get_upcoming_events':
                return $this->getUpcomingEvents($tenant);

            default:
                return "Function $functionName not implemented.";
        }
    }

    // ========== TOOL IMPLEMENTATIONS ==========

    private function searchConversations($tenant, $query)
    {
        $conversations = $tenant->conversations()
            ->whereHas('contact', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->with(['contact', 'channel', 'latestMessage'])
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'contact_name' => $c->contact?->name,
                'channel' => $c->channel?->type,
                'status' => $c->status,
                'last_message' => $c->latestMessage?->content ? substr($c->latestMessage->content, 0, 50) . '...' : 'No messages',
                'url' => route('inbox.show', $c->id)
            ]);

        return json_encode(['conversations' => $conversations, 'count' => $conversations->count()]);
    }

    private function getAiBotsSettings($tenant)
    {
        $bots = $tenant->aiBots()->get()->map(fn($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'model' => $b->model,
            'is_active' => $b->is_active,
            'is_primary' => $b->is_primary,
            'prompt_snippet' => substr($b->prompt, 0, 100) . '...'
        ]);

        return json_encode(['bots' => $bots, 'message' => 'You can configure these bots in the Conversational AI settings page.']);
    }

    private function createContact($tenant, $user, $arguments)
    {
        $name = $arguments['name'] ?? null;
        if (!$name) return json_encode(['error' => 'Contact name is required']);

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'first_name' => $arguments['first_name'] ?? null,
            'last_name' => $arguments['last_name'] ?? null,
            'emails' => isset($arguments['email']) ? [['value' => $arguments['email'], 'label' => 'Primary']] : [],
            'phones' => isset($arguments['phone']) ? [['value' => $arguments['phone'], 'label' => 'Mobile']] : [],
        ]);

        return json_encode(['success' => true, 'contact_id' => $contact->id, 'message' => "Contact '{$name}' created successfully."]);
    }

    private function createOpportunity($tenant, $user, $arguments)
    {
        $name = $arguments['name'] ?? null;
        if (!$name) return json_encode(['error' => 'Opportunity name is required']);

        $pipeline = $tenant->pipelines()->where('is_default', true)->first() ?? $tenant->pipelines()->first();
        if (!$pipeline) return json_encode(['error' => 'No pipeline found. Please create a pipeline first.']);

        $stageId = $arguments['stage_id'] ?? $pipeline->stages()->orderBy('order_index')->first()?->id;

        $opportunity = \App\Models\Opportunity::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageId,
            'contact_id' => $arguments['contact_id'] ?? null,
            'value_amount' => $arguments['value'] ?? 0,
            'owner_id' => $user->id,
            'status' => 'open'
        ]);

        return json_encode(['success' => true, 'opportunity_id' => $opportunity->id, 'message' => "Opportunity '{$name}' created in {$pipeline->name}."]);
    }

    private function getAppRoutes()
    {
        return json_encode([
            'pages' => [
                ['name' => 'Dashboard', 'path' => '/dashboard', 'description' => 'Business overview and AI pulse'],
                ['name' => 'Inbox', 'path' => '/inbox', 'description' => 'All conversations with leads'],
                ['name' => 'Opportunities', 'path' => '/opportunities', 'description' => 'Sales pipeline and deal tracking'],
                ['name' => 'Contacts', 'path' => '/contacts', 'description' => 'CRM contact management'],
                ['name' => 'Settings', 'path' => '/settings', 'description' => 'General application settings'],
                ['name' => 'Pipelines', 'path' => '/pipelines', 'description' => 'Sales funnel configuration'],
                ['name' => 'AI Chatbots', 'path' => '/bots/conversational-ai', 'description' => 'WhatsApp/Social AI agent settings'],
                ['name' => 'Knowledge Base', 'path' => '/bots/knowledge-base', 'description' => 'Train AI with your business documents'],
                ['name' => 'Social Planner', 'path' => '/marketing/social-planner', 'description' => 'Schedule social media posts'],
                ['name' => 'Brand Voice', 'path' => '/marketing/brand-voice', 'description' => 'Define AI tone and brand identity'],
                ['name' => 'Calendar', 'path' => '/calendar', 'description' => 'Appointment and task scheduling'],
            ]
        ]);
    }

    private function provideUrl($pageName)
    {
        $map = [
            'dashboard' => route('dashboard'),
            'inbox' => route('inbox.index'),
            'opportunities' => route('opportunities.index'),
            'contacts' => route('contacts.index'),
            'settings' => route('settings.index'),
            'pipelines' => route('pipelines.index'),
            'bots' => route('bots.conversational_ai'),
            'knowledge_base' => route('bots.knowledge_base'),
            'social_planner' => route('marketing.social-planner.index'),
            'brand_voice' => route('marketing.brand-voice.index'),
            'competitors' => route('marketing.competitor-pulse.index'),
            'calendar' => route('calendar.index'),
        ];

        $key = str_replace(' ', '_', strtolower($pageName));
        if (isset($map[$key])) {
            return json_encode(['url' => $map[$key], 'page' => $pageName]);
        }

        return json_encode(['error' => "Whoops! I couldn't find a page named '{$pageName}'. Maybe it's hiding? Try 'get_app_routes' to see where I can take you!", 'available_pages' => array_keys($map)]);
    }

    private function getActiveChannels($tenant)
    {
        $channels = $tenant->channels()->where('is_active', true)->get();
        if ($channels->isEmpty()) {
            return json_encode(['message' => 'No active channels found. Connect a channel to start receiving leads.']);
        }
        return json_encode($channels->map(fn($c) => [
            'name' => $c->name,
            'type' => $c->type,
            'platform_id' => $c->platform_id
        ]));
    }

    private function getUnreadCounts($tenant)
    {
        $fbCount = $tenant->conversations()
            ->whereHas('channel', fn($q) => $q->where('type', 'facebook'))
            ->where('status', 'open')
            ->count();
        $emailCount = $tenant->conversations()
            ->whereHas('channel', fn($q) => $q->whereIn('type', ['email', 'gmail', 'outlook']))
            ->where('status', 'open')
            ->count();
        return json_encode(['facebook_unread' => $fbCount, 'email_unread' => $emailCount]);
    }

    private function getPipelineSummary($tenant)
    {
        $pipeline = $tenant->pipelines()->where('is_default', true)->first() 
                    ?? $tenant->pipelines()->first();
        
        if (!$pipeline) {
            return json_encode(['message' => 'No pipeline configured yet.']);
        }

        $stages = $pipeline->stages()->orderBy('order_index')->get()->map(function($stage) {
            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'contact_count' => $stage->contacts()->count(),
                'order' => $stage->order_index
            ];
        });

        return json_encode([
            'pipeline_name' => $pipeline->name,
            'total_contacts' => $stages->sum('contact_count'),
            'stages' => $stages
        ]);
    }

    private function getLeadsNeedingAttention($tenant)
    {
        $leads = $tenant->contacts()
            ->where(function($q) {
                $q->where('rating', '>=', 4)
                  ->orWhere('stage_updated_at', '<', now()->subDays(7));
            })
            ->with(['stage', 'channel'])
            ->limit(15)
            ->get()
            ->map(function($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'rating' => $contact->rating,
                    'stage' => $contact->stage?->name ?? 'No stage',
                    'channel' => $contact->channel?->type ?? 'Unknown',
                    'avatar_url' => $contact->contact_image,
                    'days_in_stage' => $contact->stage_updated_at 
                        ? now()->diffInDays($contact->stage_updated_at)
                        : 'N/A',
                    'reason' => $contact->rating >= 4 ? 'High-value lead' : 'Stuck in stage'
                ];
            });

        return json_encode(['leads' => $leads, 'count' => $leads->count()]);
    }

    private function getContactsByStage($tenant, $stageId)
    {
        if (!$stageId) {
            return json_encode(['error' => 'stage_id is required']);
        }

        $contacts = $tenant->contacts()
            ->where('stage_id', $stageId)
            ->with(['stage', 'channel'])
            ->limit(20)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->primary_email,
                'rating' => $c->rating,
                'created_at' => $c->created_at->format('Y-m-d')
            ]);

        return json_encode(['contacts' => $contacts, 'count' => $contacts->count()]);
    }

    private function searchContacts($tenant, $query)
    {
        if (empty($query)) {
            return json_encode(['error' => 'Search query is required']);
        }

        $contacts = $tenant->contacts()
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhereJsonContains('emails', ['value' => $query])
                  ->orWhereJsonContains('phones', ['value' => $query]);
            })
            ->with(['stage', 'channel'])
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->primary_email,
                'phone' => $c->primary_phone,
                'stage' => $c->stage?->name,
                'channel' => $c->channel?->type
            ]);

        return json_encode(['contacts' => $contacts, 'count' => $contacts->count()]);
    }

    private function getContactDetails($tenant, $contactId)
    {
        if (!$contactId) {
            return json_encode(['error' => 'contact_id is required']);
        }

        $contact = $tenant->contacts()->with(['stage', 'channel', 'company'])->find($contactId);
        
        if (!$contact) {
            return json_encode(['error' => 'Contact not found']);
        }

        return json_encode([
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->primary_email,
            'phone' => $contact->primary_phone,
            'rating' => $contact->rating,
            'stage' => $contact->stage?->name,
            'stage_id' => $contact->stage_id,
            'channel' => $contact->channel?->type,
            'company' => $contact->company?->name,
            'created_at' => $contact->created_at->format('Y-m-d H:i'),
            'stage_updated_at' => $contact->stage_updated_at?->format('Y-m-d H:i')
        ]);
    }

    private function createTask($tenant, $user, $arguments)
    {
        $title = $arguments['title'] ?? null;
        if (!$title) return json_encode(['error' => 'Task title is required']);

        $task = Task::create([
            'tenant_id' => $tenant->id,
            'title' => $title,
            'description' => $arguments['description'] ?? '',
            'contact_id' => $arguments['contact_id'] ?? null,
            'due_date' => $arguments['due_date'] ?? null,
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        return json_encode(['success' => true, 'task_id' => $task->id, 'message' => 'Task created successfully']);
    }

    private function createNote($tenant, $user, $arguments)
    {
        $content = $arguments['content'] ?? null;
        if (!$content) return json_encode(['error' => 'Note content is required']);

        $note = Note::create([
            'tenant_id' => $tenant->id,
            'object_type' => Contact::class,
            'object_id' => $arguments['contact_id'] ?? null,
            'content' => $content,
            'author_id' => $user->id,
            'source' => 'system'
        ]);

        return json_encode(['success' => true, 'note_id' => $note->id, 'message' => 'Note added successfully']);
    }

    private function analyzePipelineBottlenecks($tenant)
    {
        $pipeline = $tenant->pipelines()->where('is_default', true)->first() 
                    ?? $tenant->pipelines()->first();
        
        if (!$pipeline) {
            return json_encode(['message' => 'No pipeline to analyze.']);
        }

        $bottlenecks = [];
        $stages = $pipeline->stages()->orderBy('order_index')->get();

        foreach ($stages as $stage) {
            // Dwell time for currently active contacts in this stage
            $contacts = $stage->contacts()->whereNotNull('stage_updated_at')->get();
            
            $avgDays = 0;
            if ($contacts->count() > 0) {
                $totalDays = 0;
                foreach ($contacts as $contact) {
                    $totalDays += now()->diffInDays($contact->stage_updated_at);
                }
                $avgDays = $totalDays / $contacts->count();
            }

            $contactCount = $stage->contacts()->count();

            if ($avgDays > 14 || $contactCount > 20) {
                $bottlenecks[] = [
                    'stage_name' => $stage->name,
                    'issue' => $avgDays > 14 ? 'Long dwell time' : 'High volume',
                    'contact_count' => $contactCount,
                    'avg_days_in_stage' => round($avgDays, 1)
                ];
            }
        }

        return json_encode([
            'bottlenecks' => $bottlenecks,
            'message' => empty($bottlenecks) ? 'Pipeline is flowing smoothly!' : 'Found ' . count($bottlenecks) . ' potential bottlenecks'
        ]);
    }

    private function getChannelPerformance($tenant)
    {
        $today = Carbon::today();
        $channels = $tenant->channels()->where('is_active', true)->get()->map(function($channel) use ($today) {
            return [
                'name' => $channel->name,
                'type' => $channel->type,
                'messages_today' => $channel->conversations()
                    ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                    ->where('messages.created_at', '>=', $today)
                    ->count(),
                'new_leads_today' => $channel->contacts()->where('created_at', '>=', $today)->count(),
                'total_contacts' => $channel->contacts()->count()
            ];
        });

        return json_encode(['channels' => $channels]);
    }

    private function getTrendingChannels($tenant)
    {
        $yesterday = Carbon::yesterday();
        $twoDaysAgo = Carbon::yesterday()->subDay();

        $channels = $tenant->channels()->where('is_active', true)->get()->map(function($channel) use ($yesterday, $twoDaysAgo) {
            $yesterdayCount = $channel->contacts()->where('created_at', '>=', $yesterday)->count();
            $twoDaysAgoCount = $channel->contacts()
                ->where('created_at', '>=', $twoDaysAgo)
                ->where('created_at', '<', $yesterday)
                ->count();
            
            $trend = $twoDaysAgoCount > 0 
                ? round((($yesterdayCount - $twoDaysAgoCount) / $twoDaysAgoCount) * 100, 1)
                : 0;

            return [
                'name' => $channel->name,
                'type' => $channel->type,
                'trend_percentage' => $trend,
                'trend_direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable')
            ];
        })->sortByDesc('trend_percentage')->values();

        return json_encode(['channels' => $channels]);
    }

    private function getHighValueContacts($tenant)
    {
        $contacts = $tenant->contacts()
            ->where(function($q) {
                $q->where('rating', '>=', 4)
                  ->orWhere('stage_confidence', '>=', 0.8);
            })
            ->with(['stage', 'channel'])
            ->orderByDesc('rating')
            ->limit(15)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'rating' => $c->rating,
                'stage' => $c->stage?->name,
                'channel' => $c->channel?->type,
                'confidence' => $c->stage_confidence ?? 0
            ]);

        return json_encode(['contacts' => $contacts, 'count' => $contacts->count()]);
    }

    private function getRecentContacts($tenant, $limit = 10)
    {
        $contacts = $tenant->contacts()
            ->with(['stage', 'channel'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->primary_email,
                'stage' => $c->stage?->name,
                'channel' => $c->channel?->type,
                'avatar_url' => $c->contact_image,
                'created_at' => $c->created_at->diffForHumans()
            ]);

        return json_encode(['contacts' => $contacts, 'count' => $contacts->count()]);
    }

    private function getOverdueTasks($tenant)
    {
        $tasks = Task::where('tenant_id', $tenant->id)
            ->where('due_at', '<', now())
            ->where('status', '!=', 'completed')
            ->with('object')
            ->limit(15)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'due_date' => $t->due_at?->format('Y-m-d'),
                'days_overdue' => $t->due_at ? now()->diffInDays($t->due_at) : 0,
                'related_contact' => $t->object?->name
            ]);

        return json_encode(['tasks' => $tasks, 'count' => $tasks->count()]);
    }

    private function getUpcomingEvents($tenant)
    {
        $events = $tenant->events()
            ->where('start_datetime', '>=', now())
            ->where('start_datetime', '<=', now()->addDays(7))
            ->with('contact')
            ->orderBy('start_datetime')
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start_time' => $e->start_datetime?->format('Y-m-d H:i'),
                'related_contact' => $e->contact?->name
            ]);

        return json_encode(['events' => $events, 'count' => $events->count()]);
    }

    public function syncAssistant(Tenant $tenant)
    {
        $apiKey = $tenant->openai_key ?? env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \Exception("OpenAI API Key not configured.");
        }
        $this->apiKey = $apiKey;

        $assistantId = $tenant->dashboard_assistant_id;
        if (!$assistantId) {
            return $this->ensureAssistant($tenant);
        }

        $profile = $tenant->businessProfile;
        $businessName = $profile?->business_name ?? $tenant->name;

        $response = Http::withHeaders($this->getHeaders())->post("{$this->baseUrl}/assistants/{$assistantId}", [
            'instructions' => $this->getInstructions($tenant),
            'tools' => $this->getTools(),
            'name' => "Sydkic Assistant for {$businessName}",
            // We can also update the model here if needed
            'model' => 'gpt-4o-mini'
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Failed to sync assistant: " . $response->body());
    }

    public function getInstructions(Tenant $tenant)
    {
        $profile = $tenant->businessProfile;
        $businessName = $profile?->business_name ?? $tenant->name;
        $location = $profile?->business_location ?? 'the cloud';
        $friendlyName = $profile?->friendly_business_name ?? $businessName;

        return <<<EOT
You are the friendly, slightly humorous, and highly competent business assistant for {$friendlyName}, located in {$location}. 
        You aren't just an AI; you are a key member of the team at {$businessName}, dedicated to helping the business owner (who uses Sydkic) crush their goals.
        
        Your mission: Manage leads, optimize the sales pipeline, keep the inbox clean, and provide witty but actionable insights.
        
        TONE & PERSONALITY:
        - Be friendly, enthusiastic, and occasionally funny (cracking a light joke about sales or productivity is encouraged).
        - Speak as if you are PART of the business team. Use 'we' and 'us' when referring to business goals.
        - Always be professional, but never boring.
        
        FORMATTING RULES:
        - Always use Markdown for your responses.
        - **LEAD SUMMARIES**: When listing or summarizing leads/contacts, DO NOT use standard markdown tables. Instead, use the specialized Lead Summary component by providing a JSON array of lead objects inside a fenced code block with the language `lead_summary`.
          Example:
          ```lead_summary
          [
            {"id": 123, "name": "John Doe", "channel": "messenger", "stage": "Lead", "created_at": "2 hours ago"},
            {"id": 456, "name": "Jane Smith", "channel": "instagram", "stage": "Negotiation", "created_at": "Today"}
          ]
          ```
        - Use Markdown TABLES only for other types of metrics or general data that doesn't fit the lead summary component.
        - Use Markdown LISTS for small sets of items or tasks.
        - Use INTERNAL LINKS for navigation (e.g., [Open Inbox](/inbox), [View Contact Name](/contacts/123)).
        - These links will be rendered as premium interactive buttons/cards in the UI.
        - If you aren't sure where a page is, use the 'get_app_routes' tool to find the path.
        - Avoid raw JSON in your final text response (except for the `lead_summary` code block).
        
        Always provide actionable insights. If the user has no data, give them a pep talk and guide them on what to set up first.
EOT;
    }

    public function getTools()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_contacts',
                    'description' => 'Search contacts by name, email, or phone number',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Search term']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_conversations',
                    'description' => 'Search across inbox conversations by contact name or message content snippet',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Search term']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_contact_details',
                    'description' => 'Get full details of a specific contact',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'contact_id' => ['type' => 'integer', 'description' => 'The contact ID']
                        ],
                        'required' => ['contact_id']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_contact',
                    'description' => 'Create a new contact/lead',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Full name'],
                            'email' => ['type' => 'string', 'description' => 'Email address'],
                            'phone' => ['type' => 'string', 'description' => 'Phone number'],
                            'first_name' => ['type' => 'string'],
                            'last_name' => ['type' => 'string']
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_opportunity',
                    'description' => 'Create a sales opportunity for a contact',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Opportunity title'],
                            'value' => ['type' => 'number', 'description' => 'Monetary value'],
                            'stage_id' => ['type' => 'integer', 'description' => 'Optional pipeline stage ID'],
                            'contact_id' => ['type' => 'integer', 'description' => 'Related contact ID']
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_app_routes',
                    'description' => 'Get a list of all available pages and routes in the application for navigation help',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'provide_url',
                    'description' => 'Get the URL for a specific page in the application (e.g., Inbox, Settings, Calendar)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'page_name' => ['type' => 'string', 'description' => 'Short name of the page']
                        ],
                        'required' => ['page_name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_pipeline_summary',
                    'description' => 'Get overview of all pipeline stages with contact counts',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'ai_bots_settings',
                    'description' => 'Retrieve current conversational AI bot configurations and settings',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_task',
                    'description' => 'Create a new task for the user',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'contact_id' => ['type' => 'integer'],
                            'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD']
                        ],
                        'required' => ['title']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_note',
                    'description' => 'Add a note to a contact',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'content' => ['type' => 'string'],
                            'contact_id' => ['type' => 'integer']
                        ],
                        'required' => ['content', 'contact_id']
                    ]
                ]
            ],
            // Legacy Tools
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_active_channels',
                    'description' => 'Get the list of currently connected and active channels',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_unread_counts',
                    'description' => 'Get count of unread messages by channel type',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_pipeline_bottlenecks',
                    'description' => 'Identify pipeline stages with low conversion or high dwell time',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_channel_performance',
                    'description' => 'Get detailed performance metrics for each channel',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_trending_channels',
                    'description' => 'Identify channels with increasing or decreasing activity',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_high_value_contacts',
                    'description' => 'Get contacts with high ratings or AI confidence scores',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_recent_contacts',
                    'description' => 'Get newest contacts',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'Number of contacts to return (default 10)']
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_overdue_tasks',
                    'description' => 'Find tasks past their due date',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_upcoming_events',
                    'description' => 'Get scheduled events in the next 7 days',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []]
                ]
            ]
        ];
    }

    public function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];
    }

    public function generateDailySummary(Tenant $tenant)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Collect metrics
        $channelsCount = $tenant->channels()->where('is_active', true)->count();
        $totalLeads = $tenant->contacts()
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();
            
        $newLeadsYesterday = $tenant->contacts()
            ->whereDate('created_at', $yesterday)
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })
            ->count();

        // Unread messages
        $unreadCounts = json_decode($this->getUnreadCounts($tenant), true);
        $facebookUnread = $unreadCounts['facebook_unread'] ?? 0;
        $emailUnread = $unreadCounts['email_unread'] ?? 0;

        // Overdue tasks
        $overdueTasks = json_decode($this->getOverdueTasks($tenant), true);
        $overdueCount = $overdueTasks['count'] ?? 0;

        // Pipeline Bottlenecks
        $bottlenecksData = json_decode($this->analyzePipelineBottlenecks($tenant), true);
        $bottlenecks = $bottlenecksData['bottlenecks'] ?? [];

        // Upcoming events
        $upcomingEvents = json_decode($this->getUpcomingEvents($tenant), true);
        $eventCount = $upcomingEvents['count'] ?? 0;

        // Fetch recently won opportunities (last 5)
        $recentWins = $tenant->opportunities()
            ->where('status', 'won')
            ->where('updated_at', '>=', Carbon::today()->subDays(7))
            ->with('contact')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $winsContext = "";
        if ($recentWins->count() > 0) {
            $winsContext = "\n### RECENT SUCCESSFUL DEALS (last 7 days):\n";
            foreach ($recentWins as $win) {
                if (!$win->contact) continue;
                $contactName = $win->contact->full_name;
                $conversation = $win->contact->conversations()->latest('last_message_at')->first();
                
                $linkFormat = $conversation 
                    ? "[{$contactName}](conversation:{$conversation->id})" 
                    : "[{$contactName}](contact:{$win->contact_id})";

                $isYesterday = $win->updated_at->isYesterday();
                $winDate = $isYesterday ? "Yesterday" : $win->updated_at->toDateString();
                $winsContext .= "- Deal: {$win->name}, Link: {$linkFormat}, Won: {$winDate}\n";
            }
        }

        $bottleneckContext = "";
        if (!empty($bottlenecks)) {
            $bottleneckContext = "\n### PIPELINE ISSUES:\n";
            foreach ($bottlenecks as $b) {
                $bottleneckContext .= "- Stage '{$b['stage_name']}' has {$b['issue']} ({$b['contact_count']} contacts)\n";
            }
        }

        $activityContext = "### CURRENT ACTIVITY SUMMARY:\n";
        $activityContext .= "- Active Channels: {$channelsCount}\n";
        $activityContext .= "- Unread Messages: " . ($facebookUnread + $emailUnread) . " ({$facebookUnread} FB, {$emailUnread} Email)\n";
        $activityContext .= "- Overdue Tasks: {$overdueCount}\n";
        $activityContext .= "- Upcoming Events: {$eventCount}\n";
        $activityContext .= "- New Leads Yesterday: {$newLeadsYesterday}\n";
        $activityContext .= "- Total Leads: {$totalLeads}\n";

        $prompt = "You are a proactive business growth assistant. Your task is to write a high-energy 'conversation starter' for a business dashboard.
        
        {$activityContext}{$winsContext}{$bottleneckContext}
        
        STRICT RULES:
        1. ACT AS A CONVERSATION STARTER: Write as if you are proactive greeting the user and nudging them into action.
        2. TONE: High-energy, proactive, and slightly provocative.
        3. NO FORMAL HEADERS: Never use headers like '### Business Pulse Summary' or 'Summary:'. Just start the conversation.
        4. KEEP IT CONCISE: 3-4 sentences max.
        5. IF ACTIVITY IS HIGH: Celebrate the momentum and highlight the most urgent next step.
        6. IF ACTIVITY IS LOW: Ask a provocative growth question (e.g., 'What if we launched a new lead capture form today?').
        7. HIGHLIGHT URGENCY: Menton unread messages or overdue tasks as immediate priorities.
        8. LINK FORMATTING: Always use the exact link format provided in the context (e.g., [Name](contact:ID)).";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . ($tenant->openai_key ?? env('OPENAI_API_KEY')),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a highly proactive, exciting business growth assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.8,
            'max_tokens' => 200
        ]);

        if ($response->successful()) {
            $summary = $response->json()['choices'][0]['message']['content'];
            
            $settings = $tenant->settings ?? [];
            $settings['daily_pulse_summary'] = [
                'date' => $today->toDateString(),
                'content' => $summary
            ];
            $tenant->update(['settings' => $settings]);

            return $summary;
        }

        Log::error("Failed to generate AI summary: " . $response->body());
        return "AI is analyzing your channels and leads for today's summary.";
    }
}
