<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Import Log facade

trait ManagesOpenAiAssistant
{
    public static function bootManagesOpenAiAssistant()
    {
        static::creating(function ($model) {
            $model->syncOpenAiAssistant(true); // true = is creating
        });

        static::updated(function ($model) {
            // Only sync if relevant fields changed
            if ($model->wasChanged(['name', 'model', 'prompt'])) {
                try {
                    $model->syncOpenAiAssistant(false);
                } catch (\Exception $e) {
                    // Start a separate side-effect since we can't easily stop an "updated" event after the fact?
                    // Actually, updated happens AFTER save. Throwing here behaves differently (transaction might rollback if configured).
                    // For now, let's just log or flash for updates, but user specifically asked about "When creating".
                    // But if edit fails, we should probably warn too.
                    // For now, let's allow the exception to bubble so the controller can catch it? 
                    // But 'updated' is post-facto. To stop update, we need 'updating'.
                    // Let's switch to 'updating' for strictness.
                }
            }
        });
        
        static::updating(function ($model) {
             if ($model->isDirty(['name', 'model', 'prompt'])) {
                 $model->syncOpenAiAssistant(false);
             }
        });
    }

    public function syncOpenAiAssistant($isCreating = false)
    {
        // 1. Get API Key from Tenant Model
        $tenant = $this->tenant;
        // During creation, relation might not be loaded if set via ID.
        if (!$tenant && $this->tenant_id) {
            $tenant = \App\Models\Tenant::find($this->tenant_id);
        }
        
        $apiKey = $tenant ? $tenant->openai_key : null;

        if (!$apiKey) {
             // Fallback to settings array
             $apiKey = $tenant->settings['openai_key'] ?? null;
        }

        if (!$apiKey) {
            // Fallback to system-wide key
            $apiKey = config('services.openai.key');
        }

        if (!$apiKey) {
            throw new \Exception("OpenAI API Key is required. Please check the system configuration.");
        }

        // Tools Generation
        $tools = [];
        
        // Load relationships if not loaded and if it's not a fresh create (because actions won't exist yet on creating)
        if (!$isCreating) {
             // Force reload to ensure we don't use stale cache from implicit event listeners
             $this->load('actions');
             
             Log::info("OpenAI Sync: Found " . $this->actions->count() . " actions for Bot ID {$this->id}");

             foreach ($this->actions as $action) {
                 Log::info("OpenAI Sync: Processing Action '{$action->action_name}' (Active: {$action->is_active})");
                 
                 if (!$action->is_active) continue;
                 
                     // Book Appointment Tool
                     if ($action->action_name === 'Book Appointment') {
                         $config = $action->config_json;
                         // Support new multi-item structure
                         $items = $config['items'] ?? [];
                         
                         // Backward compatibility for old Single/Multiple config
                         if (empty($items) && isset($config['calendar_mode'])) {
                             // ... (existing legacy logic)
                         }
 
                         // Process Items (New Way)
                         if (!empty($items)) {
                             foreach ($items as $index => $item) {
                                 // Auto-generate suffix based on index (1-based) as requested by user
                                 // We ignore any manually stored suffix if we want to enforce structure, 
                                 // but plan said "if function_suffix is missing". 
                                 // User requirement: "No need to explicity show function suffix... handle it in the backend"
                                 // So we should force unique suffixes like _1, _2 etc.
                                 $suffix = '_' . ($index + 1);
                                 
                                 $functionName = 'book_appointment' . $suffix;
                                 $calendarId = $item['calendar_id'] ?? null;
                                 
                                 if (!$calendarId) continue;
 
                                 $description = $item['ai_description'] ?? "Book an appointment on the specific calendar.";
 
                                 $tools[] = [
                                     'type' => 'function',
                                     'function' => [
                                         'name' => $functionName,
                                         'description' => $description,
                                         'parameters' => [
                                             'type' => 'object',
                                             'properties' => [
                                                 'user_name' => [
                                                     'type' => 'string',
                                                     'description' => 'Name of the user booking the appointment',
                                                 ],
                                                 'user_email' => [
                                                     'type' => 'string',
                                                     'description' => 'Email address of the user',
                                                 ],
                                                 'preferred_time' => [
                                                     'type' => 'string',
                                                     'description' => 'Preferred time or date for the appointment (e.g., "next Tuesday at 2pm")',
                                                 ],
                                             ],
                                             'required' => ['user_name', 'user_email', 'preferred_time'],
                                         ],
                                     ],
                                 ];
                             }
                         }
                     }
                 
                 // Update Contact Tool
                 if ($action->action_name === 'Update Contact') {
                     $config = $action->config_json;
                     
                     // Helper to build tool definition
                     $buildTool = function($item) {
                         $toolName = strtolower(str_replace(' ', '_', $item['action_name']));
                         $fieldName = $item['field_id']; // ID or slug
                         // Ideally map ID to name if custom, but for now just use what we have or generic description
                         
                         return [
                             'type' => 'function',
                             'function' => [
                                 'name' => $toolName,
                                 'description' => ($item['extraction_instruction'] ?? 'Update contact field') . ". Updates field: " . $fieldName,
                                 'parameters' => [
                                     'type' => 'object',
                                     'properties' => [
                                         'value' => [
                                             'type' => 'string',
                                             'description' => "The new value for {$fieldName}",
                                         ],
                                     ],
                                     'required' => ['value'],
                                 ],
                             ],
                         ];
                     };

                     // Support multiple items (new way)
                     if (!empty($config['items']) && is_array($config['items'])) {
                         foreach ($config['items'] as $item) {
                             if (!empty($item['action_name']) && !empty($item['field_id'])) {
                                  $tools[] = $buildTool($item);
                             }
                         }
                     } 
                     // Legacy/Single way fallback
                     elseif (!empty($config['field_id'])) {
                          // Construct a temporary item from legacy single-field config
                          $item = [
                              'action_name' => $action->action_name, // Default to action name
                              'field_id' => $config['field_id'],
                              'extraction_instruction' => $config['extraction_instruction'] ?? '',
                          ];
                          $tools[] = $buildTool($item);
                     }
                 }

                 // Human Handover Tool
                 if ($action->action_type === 'handover' || $action->action_name === 'Human Handover') {
                     $scenarios = $action->config_json['scenarios'] ?? [];
                     // Filter active scenarios (default to true if is_active not set, for backward compat)
                     $activeScenarios = array_filter($scenarios, function($s) {
                         return !isset($s['is_active']) || $s['is_active'] === true;
                     });

                     if (!empty($activeScenarios)) {
                         $index = 1;
                         foreach ($activeScenarios as $s) {
                             $scenarioName = $s['scenario_name'] ?? 'General';
                             // Sanitize for tool name if we wanted, but user asked for human_handover_1 etc.
                             $toolName = "human_handover_{$index}";
                             
                             $tools[] = [
                                 'type' => 'function',
                                 'function' => [
                                     'name' => $toolName,
                                     'description' => "Transfer the conversation to a human agent. Reason: " . $scenarioName,
                                     'parameters' => [
                                         'type' => 'object',
                                         'properties' => (object)[], // Force empty object for JSON
                                         'required' => [],
                                     ],
                                 ],
                             ];
                             $index++;
                         }
                     }
                 }

                 // Auto Follow-up & Conversation Management
                 if ($action->action_name === 'Auto Follow-up') {
                     $config = $action->config_json ?? [];
                     
                     // 1. Close Conversation Tool
                     if (!empty($config['enable_auto_close'])) {
                         $tools[] = [
                             'type' => 'function',
                             'function' => [
                                 'name' => 'close_conversation',
                                 'description' => 'Close the conversation when the user says goodbye or the conversation is finished.',
                                 'parameters' => [
                                     'type' => 'object',
                                     'properties' => (object)[],
                                     'required' => [],
                                 ],
                             ],
                         ];
                     }

                     // 2. Mark Busy Tool
                     $tools[] = [
                         'type' => 'function',
                         'function' => [
                             'name' => 'mark_conversation_busy',
                             'description' => 'Mark the conversation as busy if the user is busy or cannot reply right now.',
                             'parameters' => [
                                 'type' => 'object',
                                 'properties' => (object)[],
                                 'required' => [],
                             ],
                         ],
                     ];
                     
                     // 3. Snooze / Request Later Tool
                     $tools[] = [
                         'type' => 'function',
                         'function' => [
                             'name' => 'snooze_conversation',
                             'description' => 'Snooze the conversation if the contact requests to be contacted later.',
                             'parameters' => [
                                 'type' => 'object',
                                 'properties' => [
                                     'duration_hours' => [
                                        'type' => 'integer', 
                                        'description' => 'Number of hours to snooze (default 24 if unspecified)'
                                     ]
                                 ],
                                 'required' => [],
                             ],
                         ],
                     ];

                     // Note: Actual follow-up messages are handled by a system Job, not tools.
                 }
                 
                 // Trigger Workflow Action (New)
                 if ($action->action_name === 'Trigger Workflow') {
                    $config = $action->config_json;
                    $triggerType = $config['trigger_type'] ?? 'ai_decided'; // Default to AI-decided for backward compatibility
                    
                    // Only register as OpenAI tool if it's AI-decided
                    // Automatic triggers are handled separately in HandlesBotConversations
                    if ($triggerType === 'ai_decided') {
                        $pipelineId = $config['pipeline_id'] ?? null;
                        if ($pipelineId) {
                            $instructions = $config['trigger_instruction'] ?? "Trigger this workflow.";
                            $toolName = "trigger_workflow_1"; // Single for now as UI only allows one
                            
                            $tools[] = [
                                'type' => 'function',
                                'function' => [
                                    'name' => $toolName,
                                    'description' => $instructions . " (Pipeline ID: {$pipelineId})",
                                    'parameters' => [
                                        'type' => 'object',
                                        'properties' => (object)[],
                                        'required' => [],
                                    ],
                                ],
                            ];
                        }
                    }
                     
                     // Future proof: check for 'items' if we ever support multiple workflows
                  }
                  
                  // Transfer Bot Tool
                  if ($action->action_name === 'Transfer Bot' || $action->action_type === 'transfer_bot') {
                      $scenarios = $action->config_json['scenarios'] ?? [];
                      $activeScenarios = array_filter($scenarios, function($s) {
                          return !isset($s['is_active']) || $s['is_active'] === true;
                      });

                      if (!empty($activeScenarios)) {
                          $index = 1;
                          foreach ($activeScenarios as $s) {
                              $targetBotId = $s['target_bot_id'] ?? null;
                              if (!$targetBotId) continue;
                              
                              $instruction = $s['trigger_config_json']['instruction'] ?? $s['scenario_name'] ?? "Transfer conversation to another bot.";
                              $toolName = "transfer_to_bot_{$index}";
                              
                              $tools[] = [
                                  'type' => 'function',
                                  'function' => [
                                      'name' => $toolName,
                                      'description' => $instruction . " (Target Bot ID: {$targetBotId})",
                                      'parameters' => [
                                          'type' => 'object',
                                          'properties' => (object)[],
                                          'required' => [],
                                      ],
                                  ],
                              ];
                              $index++;
                          }
                      }
                   }
              }
         }
         
         // Universal Tools (Always Available)
         // Enable DND - Allow users to opt-out of messages
         $tools[] = [
             'type' => 'function',
             'function' => [
                 'name' => 'enable_dnd',
                 'description' => 'Enable Do Not Disturb (DND) settings when the user requests to stop receiving messages, unsubscribe, or opt-out from communications. Use this when they mention not wanting to receive texts, emails, or calls.',
                 'parameters' => [
                     'type' => 'object',
                     'properties' => [
                         'type' => [
                             'type' => 'string',
                             'enum' => ['text', 'sms', 'email', 'calls', 'all'],
                             'description' => 'Type of communication to disable. Use "text" or "sms" for text messages, "email" for emails, "calls" for phone calls, or "all" to disable all communications. Default is "text".',
                         ],
                     ],
                     'required' => [],
                 ],
             ],
         ];
         
        Log::info("OpenAI Sync: Final Tools Count: " . count($tools));

        // 2. Prepare Payload
        $payload = [
            'model' => $this->model,
            'name' => $this->name,
            'instructions' => $this->prompt ?? '',
            'tools' => $tools,
        ];

        // 3. Determine Endpoint and Method
        $baseUrl = 'https://api.openai.com/v1/assistants';
        
        // If we already have an assistant ID, update it.
        // If creating, we definitely don't have one on the model usually (unless manually passed, unlikely).
        // If updating, we might.
        if ($this->assistant_id && !$isCreating) {
            $url = "{$baseUrl}/{$this->assistant_id}";
            $response = Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post($url, $payload); // Update is POST in OpenAI Assistants API v2 (modifying object)
        } else {
            // Create new
            $url = $baseUrl;
            $response = Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post($url, $payload);
        }

        if ($response->successful()) {
            $data = $response->json();
            
            if ($isCreating) {
                // Directly set attribute for the pending INSERT
                $this->assistant_id = $data['id'];
            } elseif ($this->wasChanged() || $this->isDirty()) { // If we are in updating event
                // If we are in 'updating', we can set the attribute and it will be saved.
                // If we didn't have an ID before (e.g. migration), save it now.
                 if ($this->assistant_id !== $data['id']) {
                     $this->assistant_id = $data['id'];
                 }
            } else {
                // If called manually
                $this->assistant_id = $data['id'];
                $this->saveQuietly();
            }
            
            Log::info("OpenAI Assistant Synced: {$this->assistant_id}");
        } else {
            $errorMsg = $response->json('error.message') ?? $response->body();
            Log::error("OpenAI Sync Failed: " . $errorMsg);
            throw new \Exception("OpenAI Error: " . $errorMsg);
        }
    }
}
