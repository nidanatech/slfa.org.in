<?php

namespace App\Traits;

use App\Models\BotSession;
use App\Models\Message;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HandlesBotConversations
{
    /**
     * Process an incoming message and generate a bot response.
     * 
     * @param Message $incomingMessage
     * @return void
     */
    public function processBotResponse(Message $incomingMessage)
    {
        Log::info("HandlesBotConversations: processBotResponse started for Bot ID: {$this->id}, Message ID: {$incomingMessage->id}");
        // 1. Check for Active Bot on this Channel
        // The Trait is assumed to be used by the Channel Controller or Service, 
        // OR the AiBot model itself uses this trait?
        // User said "have a trait handle... controllers job is only send and receive".
        // It's cleaner if the AiBot model uses this trait (as "Bot Logic"), or a generic Service uses it.
        // Let's assume we call this ON an AiBot instance: $bot->processBotResponse($msg)
        // So $this refers to the AiBot.
        
        if (!$this->assistant_id) {
            Log::warning("Bot {$this->id} has no assistant_id. Skipping response.");
            return;
        }

        $conversation = $incomingMessage->conversation;
        if (!$conversation) {
            Log::error("Message {$incomingMessage->id} has no conversation.");
            return;
        }

        // 1.1 Check Contact DND Settings
        $contact = $conversation->contact;
        if ($contact && $contact->isDndForChannel($conversation->channel?->type)) {
            Log::info("HandlesBotConversations: Skipping response for Conversation {$conversation->id} because Contact {$contact->id} has DND enabled for channel {$conversation->channel?->type}");
            return;
        }

        // 1.2 Check for Opt-Out Keywords
        $cleanContent = trim(strtolower($incomingMessage->content));
        $optOutKeywords = ['stop', 'unsubscribe', 'quit', 'end', 'cancel', 'opt-out'];
        
        if (in_array($cleanContent, $optOutKeywords)) {
            Log::info("HandlesBotConversations: Opt-out keyword detected: {$cleanContent}. Disabling bot for conversation {$conversation->id}");
            $conversation->update([
                'bot_disabled_until' => now()->addYears(1),
                'status' => 'open' // Re-open for human intervention if needed
            ]);
            return;
        }

        // Check for Bot Suspension
        if ($conversation->bot_disabled_until && now()->lt($conversation->bot_disabled_until)) {
             Log::info("Bot disabled for conversation {$conversation->id} until {$conversation->bot_disabled_until}");
             return;
        }

        // 1.5 Smart Response Filtering
        if ($this->config['smart_filtering'] ?? false) {
            $lastInboundMessages = $conversation->messages()
                ->where('direction', 'inbound')
                ->where('id', '!=', $incomingMessage->id)
                ->orderBy('created_at', 'desc')
                ->take(2)
                ->get();
            
            $currentContent = trim($incomingMessage->content);
            $duplicateCount = 0;
            
            foreach ($lastInboundMessages as $prevMsg) {
                if (trim($prevMsg->content) === $currentContent) {
                    $duplicateCount++;
                } else {
                    break; // Stop if it's not a consecutive duplicate
                }
            }
            
            if ($duplicateCount > 0) {
                // Check for low-value content: 
                // 1. Very short (< 10 chars)
                // 2. Contains only emojis/symbols
                $isShort = mb_strlen($currentContent) < 10;
                $isEmojiOnly = preg_match('/^[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F1E6}-\x{1F1FF}]+$/u', $currentContent);
                $isHandledAsRepetitive = $duplicateCount >= 2; // Stop if same message 3 times

                if (($duplicateCount >= 1 && ($isShort || $isEmojiOnly)) || $isHandledAsRepetitive) {
                    Log::info("HandlesBotConversations: Skipping response for Bot {$this->id} due to Smart Filtering. Duplicate: $duplicateCount, Short: " . ($isShort?'Y':'N') . ", Emoji: " . ($isEmojiOnly?'Y':'N'));
                    return;
                }
            }
        }
        
        // Clear any scheduled follow-up and reset count as interaction is happening
        $conversation->update([
            'next_followup_at' => null,
            'automated_followup_count' => 0
        ]);

        // 2. Find or Create Bot Session (Thread context)
        $session = BotSession::firstOrCreate(
            [
                'bot_id' => $this->id,
                'conversation_id' => $conversation->id,
                'tenant_id' => $this->tenant_id
            ],
            [
                'channel' => $conversation->channel->type ?? 'unknown',
                'started_at' => now(),
            ]
        );

        // Check and trigger automatic workflows (e.g., on conversation start)
        $this->checkAndTriggerAutomaticWorkflows($conversation, 'conversation_start');

        $apiKey = $this->tenant->openai_key ?? $this->tenant->settings['openai_key'] ?? config('services.openai.key');
        if (!$apiKey) {
            Log::error("No OpenAI API Key found for Tenant {$this->tenant_id} or system config.");
            return;
        }

        $baseUrl = 'https://api.openai.com/v1';
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];

        // 3. Ensure Thread Exists
        if (!$session->thread_id) {
            $response = Http::withHeaders($headers)->post("$baseUrl/threads");
            if ($response->successful()) {
                $session->update(['thread_id' => $response->json()['id']]);
            } else {
                Log::error("Failed to create OpenAI thread: " . $response->body());
                return;
            }
        }
        $threadId = $session->thread_id;

        // 4. Search Knowledge Base (RAG)
        $messageContent = $incomingMessage->content;
        $knowledgeContext = "";
        
        try {
            // A. Fetch Core Business Identity from BusinessProfile if available
            $businessProfile = \App\Models\BusinessProfile::where('tenant_id', $this->tenant_id)->first();
            if ($businessProfile) {
                $knowledgeContext .= "### CORE BUSINESS IDENTITY ###\n";
                $knowledgeContext .= "Business Name: " . ($businessProfile->friendly_name ?? 'Our Business') . "\n";
                if (!empty($businessProfile->legal_name)) $knowledgeContext .= "Legal Name: " . $businessProfile->legal_name . "\n";
                if (!empty($businessProfile->industry)) $knowledgeContext .= "Industry: " . $businessProfile->industry . "\n";
                if (!empty($businessProfile->description)) $knowledgeContext .= "Description: " . $businessProfile->description . "\n";
                $knowledgeContext .= "\n";
            }

            // B. Learned Knowledge Search (High Priority)
            $learnedKnowledge = "";
            $trainingChunks = \App\Models\BotTrainingData::where('bot_id', $this->id)
                ->whereNotNull('embedding')
                ->get();
            
            if ($trainingChunks->isNotEmpty()) {
                $embeddingService = new \App\Services\EmbeddingService($apiKey);
                $queryEmbedding = $embeddingService->generate($incomingMessage->content);
                
                $bestMatch = null;
                $highestSimilarity = 0;
                
                foreach ($trainingChunks as $chunk) {
                    $similarity = $embeddingService->cosineSimilarity($queryEmbedding, $chunk->embedding);
                    if ($similarity > $highestSimilarity) {
                        $highestSimilarity = $similarity;
                        $bestMatch = $chunk;
                    }
                }
                
                if ($bestMatch && $highestSimilarity >= 0.85) {
                    $learnedKnowledge .= "### LEARNED KNOWLEDGE (PRIORITY) ###\n";
                    $learnedKnowledge .= "A similar question was asked: \"{$bestMatch->question}\"\n";
                    $learnedKnowledge .= "The approved answer was: \"{$bestMatch->answer}\"\n";
                    $learnedKnowledge .= "If the current user's intent matches this question, use the approved answer as your primary source and reframe it naturally.\n\n";
                    Log::info("Bot {$this->id} found learned knowledge match: similarity " . round($highestSimilarity, 2));
                }
            }

            // C. Semantic Search in regular Knowledge Bases
            $searchService = new \App\Services\KnowledgeSearchService();
            $searchResults = $searchService->search($this, $incomingMessage->content, 5);
            
            if ($searchResults->isNotEmpty()) {
                $knowledgeContext .= "### KNOWLEDGE BASE CONTEXT ###\n";
                $knowledgeContext .= $searchService->formatAsContext($searchResults);
            }
            
            if (!empty($knowledgeContext) || !empty($learnedKnowledge)) {
                Log::info("Adding knowledge context for bot {$this->id}");
                // Prepend knowledge context to the message
                $messageContent = "BELOW IS THE CONTEXT TO BASE YOUR ANSWER ON:\n\n" . $learnedKnowledge . $knowledgeContext . "\n\n### USER QUESTION ###\n" . $incomingMessage->content;
            }
        } catch (\Exception $e) {
            Log::error("Knowledge search failed: " . $e->getMessage());
            // Continue without knowledge context
        }

        // 5. Add User Message to Thread (with knowledge context if available)
        $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/messages", [
            'role' => 'user',
            'content' => $messageContent,
        ]);
        
        if (!$response->successful()) {
             Log::error("Failed to add message to thread: " . $response->body());
             return;
        }

        // 5. Run Assistant
        // Helper to handle polling and tool calls
        $this->runAssistantLoop($threadId, $headers, $baseUrl, $conversation);
    }

    protected function runAssistantLoop($threadId, $headers, $baseUrl, $conversation)
    {
        // Start Run
        $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs", [
            'assistant_id' => $this->assistant_id,
        ]);

        if (!$response->successful()) {
            Log::error("Failed to start run: " . $response->body());
            return;
        }

        $runId = $response->json()['id'];
        $status = 'queued';
        $attempts = 0;
        $maxAttempts = 60; // 60 seconds timeout

        while (in_array($status, ['queued', 'in_progress', 'requires_action']) && $attempts < $maxAttempts) {
            sleep(1);
            $attempts++;

            $response = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/runs/$runId");
            if (!$response->successful()) {
                Log::error("Failed to check run status");
                return;
            }

            $runData = $response->json();
            $status = $runData['status'];

            if ($status === 'requires_action') {
                // Handle Tools
                $toolOutputs = $this->executeTools($runData['required_action']['submit_tool_outputs']['tool_calls'] ?? [], $conversation);
                
                // Submit Outputs
                $submitResponse = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs/$runId/submit_tool_outputs", [
                    'tool_outputs' => $toolOutputs,
                ]);

                if (!$submitResponse->successful()) {
                     Log::error("Failed to submit tool outputs: " . $submitResponse->body());
                     return;
                }
                $status = 'in_progress'; // Continue polling
            }
        }

        if ($status === 'completed') {
            // Get Assistant Response
            $response = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/messages?limit=1");
            $messages = $response->json()['data'] ?? [];
            $lastMessage = $messages[0] ?? null;

            if ($lastMessage && $lastMessage['role'] === 'assistant') {
                $content = '';
                foreach ($lastMessage['content'] as $block) {
                    if ($block['type'] === 'text') {
                        $content .= $block['text']['value'];
                    }
                }

                if (!empty($content)) {
                    // Create Outbound Message
                    $outboundMessage = Message::create([
                        'conversation_id' => $conversation->id,
                        'direction' => 'outbound',
                        'type' => 'text',
                        'content' => $content,
                        'sender_type' => 'bot',
                        'status' => 'pending', // Mark as pending until delivered
                    ]);

                    // Dispatch to External Platform
                    $delivered = $this->dispatchOutboundMessage($outboundMessage, $conversation);
                    
                    if ($delivered) {
                        $outboundMessage->update(['status' => 'sent']);
                    } else {
                        $outboundMessage->update(['status' => 'failed']);
                    }
                    
                    // Log Usage
                    if (isset($runData['usage'])) {
                         \App\Models\LlmTokenUsage::create([
                            'tenant_id' => $this->tenant_id,
                            'bot_id' => $this->id,
                            'bot_session_id' => null, // We should pass session ID if we had it in scope
                            'conversation_id' => $conversation->id,
                            'contact_id' => $conversation->contact_id,
                            'provider' => 'openai',
                            'model' => $this->model,
                            'prompt_tokens' => $runData['usage']['prompt_tokens'],
                            'completion_tokens' => $runData['usage']['completion_tokens'],
                            'total_tokens' => $runData['usage']['total_tokens'],
                            'request_type' => 'chat_reply',
                            'purpose' => 'auto_reply',
                        ]);
                    }
                }
            }
            
            // Interaction finished (Auto Reply sent or not required).
            // Schedule next follow-up based on config, now that Bot is done talking.
            $this->scheduleNextFollowup($conversation);

        } else {
            Log::error("HandlesBotConversations: Run failed or timed out. Status: $status");
        }
    }

    /**
     * Dispatch an outbound message to the relevant external platform.
     */
    public function dispatchOutboundMessage(Message $message, $conversation)
    {
        $channel = $conversation->channel;
        if (!$channel) {
            Log::warning("HandlesBotConversations: Cannot dispatch message {$message->id}, no channel found.");
            return false;
        }

        $contact = $conversation->contact;
        $channelType = $channel->type;
        $content = $message->content;
        $result = null;

        Log::info("HandlesBotConversations: Dispatching outbound message to platform: {$channelType}");

        try {
            switch ($channelType) {
                case 'messenger':
                    $recipientId = $contact->external_id ?? null;
                    if ($recipientId) {
                        $service = new \App\Services\FacebookService($channel);
                        $result = $service->sendMessage($recipientId, $content);
                    }
                    break;

                case 'instagram':
                case 'instagram_message':
                    $recipientId = $contact->external_id ?? null;
                    if ($recipientId) {
                        $service = new \App\Services\InstagramService($channel);
                        $result = $service->sendMessage($recipientId, $content);
                    }
                    break;

                case 'google_business':
                    $reviewName = $conversation->meta['review_name'] ?? $conversation->metadata['review_name'] ?? null;
                    if ($reviewName) {
                        $service = new \App\Services\GoogleBusinessService($channel);
                        $result = $service->replyToReview($reviewName, $content);
                    }
                    break;

                case 'threads':
                    $threadId = $conversation->meta['thread_id'] ?? $conversation->metadata['thread_id'] ?? $conversation->external_id ?? null;
                    if ($threadId) {
                        $service = new \App\Services\ThreadsService($channel);
                        $result = $service->replyToThread($threadId, $content);
                    }
                    break;

                case 'api':
                case 'web':
                case 'wordpress':
                    // These platforms typically poll or use webhooks. 
                    // No direct async dispatch needed unless using WebSockets.
                    Log::info("HandlesBotConversations: No async dispatch needed for {$channelType}");
                    return true;

                default:
                    Log::warning("HandlesBotConversations: No delivery handler for channel type '{$channelType}'");
                    return false;
            }

            if ($result) {
                // Update message with external ID if available
                $externalId = $result['message_id'] ?? $result['id'] ?? null;
                if ($externalId) {
                    $meta = $message->meta ?? [];
                    $meta['external_id'] = $externalId;
                    $message->update(['meta' => $meta]);
                }
                Log::info("HandlesBotConversations: Outbound message delivered successfully to {$channelType}");
                return true;
            } else {
                Log::error("HandlesBotConversations: Delivery failed for message {$message->id} on {$channelType}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("HandlesBotConversations: Dispatch Error: " . $e->getMessage(), [
                'channel' => $channelType,
                'message_id' => $message->id
            ]);
            return false;
        }
    }

    protected function executeTools($toolCalls, $conversation = null)
    {
        $outputs = [];
        foreach ($toolCalls as $call) {
            $functionName = $call['function']['name'];
            $args = json_decode($call['function']['arguments'], true) ?? [];
            $output = ['status' => 'error', 'message' => 'Tool execution failed'];

            try {
                // Here we would delegate to Action Classes or specialized handlers
                // For now, basic mocks/implementations
                if ($functionName === 'book_appointment') {
                     // Non-suffixed call - likely old configuration or AI confusion.
                     // We should return an error message to the AI asking it to use the suffixed version if available,
                     // or just inform that the configuration is invalid.
                     $output = ['status' => 'error', 'message' => 'Generic book_appointment tool called. Please use the specific calendar tools (e.g., book_appointment_1).'];
                } elseif (str_starts_with($functionName, 'book_appointment_')) {
                     // Dynamic way: extract suffix index
                     $parts = explode('_', $functionName);
                     $index = end($parts);
                     
                     if (is_numeric($index)) {
                         $arrayIndex = (int)$index - 1;
                         
                         $action = $this->actions->where('action_name', 'Book Appointment')->first();
                         if ($action) {
                             $config = $action->config_json;
                             $items = $config['items'] ?? [];
                             $targetItem = $items[$arrayIndex] ?? null;
                             
                             if ($targetItem) {
                                 $calendarId = $targetItem['calendar_id'];
                                 $pipelineId = $targetItem['pipeline_id'] ?? null;
                                 
                                 // 1. Find or Create Contact
                                 $email = $args['user_email'] ?? null;
                                 $name = $args['user_name'] ?? 'Guest';
                                 $contact = null;

                                 if ($email) {
                                     // Basic lookup by email for this tenant
                                     // SQLite compatible check using LIKE string matching for JSON
                                     $contact = \App\Models\Contact::where('tenant_id', $this->tenant_id)
                                         ->where(function($q) use ($email) {
                                             $q->where('emails', 'like', '%"value":"' . $email . '"%')
                                               ->orWhere('emails', 'like', '%"' . $email . '"%');
                                         })
                                         ->first();

                                     if (!$contact) {
                                         // Create new contact
                                         $nameParts = explode(' ', $name, 2);
                                         $firstName = $nameParts[0];
                                         $lastName = $nameParts[1] ?? '';
                                         
                                         $contact = \App\Models\Contact::create([
                                             'tenant_id' => $this->tenant_id,
                                             'channel_id' => $conversation ? $conversation->channel_id : null, 
                                             'external_id' => 'bot_' . uniqid(), // Required field
                                             'name' => $name,
                                             'first_name' => $firstName,
                                             'last_name' => $lastName,
                                             'emails' => [['value' => $email, 'type' => 'work']],
                                             // 'source' => 'bot_appointment', // Not in fillable/schema
                                         ]);
                                     }
                                 }

                                 // 2. Trigger Workflow / Create Opportunity if Pipeline Selected
                                 $pipelineMsg = "";
                                 $opp = null;
                                 if ($pipelineId && $contact) {
                                     $pipeline = \App\Models\Pipeline::find($pipelineId);
                                     if ($pipeline) {
                                         $firstStage = $pipeline->stages()->orderBy('order_index')->first();
                                         if ($firstStage) {
                                             $opp = \App\Models\Opportunity::create([
                                                 'tenant_id' => $this->tenant_id,
                                                 'pipeline_id' => $pipeline->id,
                                                 'stage_id' => $firstStage->id,
                                                 'contact_id' => $contact->id,
                                                 'name' => $contact->name ?? $name,
                                                 'status' => 'open',
                                                 'source' => 'chat', // Enum: chat, manual, api
                                                 'value_amount' => 0,
                                                 'currency' => 'USD'
                                             ]);

                                             // Add Audit Note
                                             \App\Models\Note::create([
                                                 'tenant_id' => $this->tenant_id,
                                                 'object_type' => \App\Models\Opportunity::class,
                                                 'object_id' => $opp->id,
                                                 'content' => "Opportunity created automatically via AI Bot 'Book Appointment' tool.",
                                                 'source' => 'automation',
                                             ]);

                                             $pipelineMsg = " and added to pipeline '{$pipeline->name}'";
                                         }
                                     }
                                 }
                                 
                                 // 3. Create Calendar Event
                                 if ($calendarId && $contact) {
                                     try {
                                         $startTime = Carbon::parse($args['preferred_time']);
                                         $endTime = (clone $startTime)->addHour();
                                         
                                         \App\Models\Event::create([
                                             // 'tenant_id' => $this->tenant_id, // Table does not have tenant_id
                                             'calendar_id' => $calendarId,
                                             'contact_id' => $contact->id,
                                             'opportunity_id' => $opp ? $opp->id : null,
                                             'conversation_id' => $conversation ? $conversation->id : null,
                                             'title' => "Appointment: " . ($contact->name ?? 'Guest'),
                                             'description' => "Requested via AI Bot. Preferred time: " . $args['preferred_time'],
                                             'start_datetime' => $startTime,
                                             'end_datetime' => $endTime,
                                             'status' => 'confirmed',
                                             'source' => 'bot',
                                             'timezone' => $this->tenant->timezone ?? config('app.timezone'),
                                         ]);
                                         $pipelineMsg .= " and scheduled in the calendar";
                                         
                                         // 4. Return Success for AI (Move inside try block)
                                         $output = [
                                             'status' => 'success', 
                                             'message' => "Appointment successfully booked for {$args['preferred_time']}. Contact secured{$pipelineMsg}."
                                         ];
                                     } catch (\Exception $e) {
                                         Log::error("Failed to schedule appointment: " . $e->getMessage());
                                         $output = ['status' => 'error', 'message' => "Failed to schedule appointment: " . $e->getMessage()];
                                     }
                                 } else {
                                     $output = ['status' => 'error', 'message' => "Missing calendar or contact information."];
                                 }

                             } else {
                                 $output = ['status' => 'error', 'message' => "Configuration for $functionName not found (Index $index)."];
                             }
                         } else {
                             $output = ['status' => 'error', 'message' => "Book Appointment action not enabled or configured."];
                         }
                     } else {
                         $output = ['status' => 'error', 'message' => "Invalid function name format: $functionName"];
                     }

                } elseif (str_starts_with($functionName, 'human_handover_')) {
                     // 1. Parse Suffix
                     $parts = explode('_', $functionName);
                     $index = end($parts);
                     
                     $scenario = null;
                     $action = $this->actions->where('action_name', 'Human Handover')->first(); // Or type 'handover'
                     if (!$action) {
                          // Fallback check
                          foreach($this->actions as $act) {
                              if ($act->action_type === 'handover') {
                                  $action = $act; 
                                  break;
                              }
                          }
                     }

                     if ($action) {
                         $scenarios = $action->config_json['scenarios'] ?? [];
                         // Filter active like in ManagesOpenAiAssistant
                         $activeScenarios = array_values(array_filter($scenarios, function($s) {
                             return !isset($s['is_active']) || $s['is_active'] === true;
                         }));
                         
                         // Check bounds (index is 1-based suffix)
                         $arrayIndex = (int)$index - 1;
                         if (isset($activeScenarios[$arrayIndex])) {
                             $scenarioConfig = $activeScenarios[$arrayIndex]; 
                             // Might be array if from JSON, but we might want the DB Model if possible? 
                             // The `bot_action_scenarios` table exists and ManagesOpenAiAssistant uses the JSON.
                             // Let's try to find the DB record to be safe/clean if we have the ID, but the tool generation was based on the JSON array order.
                             // We don't have the DB ID in the tool name, just the index.
                             // Ideally we should have put the ID in tool name, but index is what we have.
                             // Let's rely on the DB Relationship to find the real scenario record matching the config logic.
                             // Or simpler: Iterate the Action's scenarios related model.
                             
                             // Let's load the scenario model to get the IDs (users, tags etc) which might be stored as IDs in JSON anyway.
                             // Actually, the structure in migration showed `bot_action_scenarios` table.
                             // We should probably rely on that if populated.
                             $dbScenarios = \App\Models\BotActionScenario::where('bot_action_id', $action->id)
                                 ->where('is_active', true)
                                 ->get(); // Order is not guaranteed to match array filter unless we sort by id? 
                                 // The tool generation iterates the JSON 'scenarios' key. 
                                 // We must assume the JSON and the DB are in sync or use the JSON for logic if simpler.
                                 // The user request mentions "setting saved in the bot_action_scenarios table".
                                 // So let's use the DB model.
                                 
                                 if ($dbScenarios->count() > $arrayIndex) {
                                     $scenario = $dbScenarios->values()->get($arrayIndex);
                                 }
                         }
                     }
                     
                     if ($conversation) {
                         // 2. Logic: Update Conversation Status
                         $conversation->update(['status' => 'open']);
                         
                         if ($scenario) {
                             Log::info("Handover: Using Scenario ID {$scenario->id}");
                             
                             // Assign User
                             if ($scenario->assigned_user_id) {
                                 $conversation->update(['assigned_to' => $scenario->assigned_user_id]);
                             }
                             
                             // Add Tag
                             if ($scenario->add_contact_tag_id && $conversation->contact) {
                                 try {
                                     $conversation->contact->tags()->syncWithoutDetaching([$scenario->add_contact_tag_id]);
                                     Log::info("Handover: Added Tag {$scenario->add_contact_tag_id} to Contact {$conversation->contact->id}");
                                 } catch (\Exception $e) {
                                     Log::error("Handover: Tag Error: " . $e->getMessage());
                                 }
                             }
                             
                             // Create Task
                             if ($scenario->create_task && $scenario->assigned_user_id) {
                                 \App\Models\Task::create([
                                     'tenant_id' => $this->tenant_id,
                                     'title' => 'Bot Handover: ' . ($conversation->contact->name ?? 'Unknown Contact'),
                                     'description' => 'Conversation handed over from bot due to scenario: ' . $scenario->scenario_name,
                                     'owner_id' => $scenario->assigned_user_id,
                                     'created_by' => $scenario->assigned_user_id, // Or bot owner?
                                     // Polymorphic relation
                                     'object_type' => get_class($conversation),
                                     'object_id' => $conversation->id,
                                     'due_at' => now()->addHours(2), // Default deadline
                                 ]);
                             }
                             
                             // Send Final Message
                             if (!empty($scenario->final_message)) {
                                 try {
                                    $m = Message::create([
                                        'conversation_id' => $conversation->id,
                                        'direction' => 'outbound',
                                        'type' => 'text',
                                        'content' => $scenario->final_message,
                                        'sender_type' => 'bot',
                                    ]);
                                    Log::info("Handover: Message Created ID {$m->id}");
                                 } catch (\Exception $e) {
                                     Log::error("Handover: Message Error: " . $e->getMessage());
                                 }
                             }
                             
                            // Disable Bot
                            if ($scenario->reactivate_after_hours > 0) {
                                $conversation->update([
                                    'bot_disabled_until' => now()->addHours($scenario->reactivate_after_hours)
                                ]);
                            }

                            // Trigger automatic workflows for bot handover
                            $this->checkAndTriggerAutomaticWorkflows($conversation, 'bot_handover');
                        }
                    }
                     
                     $msg = 'Handover initialized. ';
                     if ($scenario && $scenario->final_message) {
                         $msg .= "Final message sent. ";
                     }
                     $msg .= "Bot disabled temporarily.";
                     
                     $output = ['status' => 'success', 'message' => $msg];


                } elseif (str_starts_with($functionName, 'human_handover')) {
                     // Legacy single-mode fallback
                     if ($conversation) {
                         $conversation->update(['status' => 'open']);
                         // Trigger automatic workflows for bot handover
                         $this->checkAndTriggerAutomaticWorkflows($conversation, 'bot_handover');
                     }
                     $output = ['status' => 'success', 'message' => 'Handover initiated.'];

                } elseif (str_starts_with($functionName, 'trigger_workflow_')) {
                    // 1. Get Action Config
                    $action = $this->actions->where('action_name', 'Trigger Workflow')->first();
                    $triggeredMap = false;
                    
                    if ($action && $conversation && $conversation->contact) {
                         // Parse index if we support multi-workflow later
                         // For now, index 1 maps to the single config
                         $index = (int)str_replace('trigger_workflow_', '', $functionName);
                         $config = $action->config_json;
                         
                         $pipelineId = $config['pipeline_id'] ?? null;
                         
                         if ($pipelineId) {
                             $pipeline = \App\Models\Pipeline::find($pipelineId);
                             if ($pipeline) {
                                 // Create Opportunity in First Stage
                                 $firstStage = $pipeline->stages()->orderBy('order_index')->first();
                                  if ($firstStage) {
                                      // Create Opportunity
                                      $opp = \App\Models\Opportunity::create([
                                          'tenant_id' => $this->tenant_id,
                                          'name' => $conversation->contact->name ?? 'New Lead',
                                          'pipeline_id' => $pipeline->id,
                                          'stage_id' => $firstStage->id,
                                          'contact_id' => $conversation->contact->id,
                                          'status' => 'open',
                                          'source' => 'chat', // Valid values: 'chat', 'manual', 'api'
                                          'owner_id' => $conversation->assigned_to ?? null, // Assign to bot handler if set
                                          'expected_close_date' => now()->addDays(7),
                                      ]);

                                      // Add Audit Note
                                      \App\Models\Note::create([
                                          'tenant_id' => $this->tenant_id,
                                          'object_type' => \App\Models\Opportunity::class,
                                          'object_id' => $opp->id,
                                          'content' => "Opportunity created automatically via AI Bot 'Trigger Workflow' tool.",
                                          'source' => 'automation',
                                      ]);
                                      
                                      $output = ['status' => 'success', 'message' => "Workflow triggered. Opportunity '{$opp->name}' created in pipeline '{$pipeline->name}'."];
                                      $triggeredMap = true;
                                  }
 else {
                                     $output = ['status' => 'error', 'message' => "Pipeline '{$pipeline->name}' has no stages defined."];
                                     $triggeredMap = true; // Handled error gracefully
                                 }
                             }
                         }
                    }
                    
                    if (!$triggeredMap) {
                        $output = ['status' => 'error', 'message' => "Workflow configuration not found or valid."];
                    }

                } elseif (str_starts_with($functionName, 'transfer_to_bot_')) {
                    // Transfer Bot Logic
                    $action = $this->actions->where('action_name', 'Transfer Bot')->first();
                    $transferred = false;
                    
                    if ($action && $conversation) {
                         $index = (int)str_replace('transfer_to_bot_', '', $functionName);
                         $scenarios = $action->config_json['scenarios'] ?? [];
                         
                         // Account for 1-based index
                         // Filter active scenarios first to match generation order
                         $activeScenarios = array_values(array_filter($scenarios, function($s) {
                              return !isset($s['is_active']) || $s['is_active'] === true;
                         }));
                         
                         $scenario = $activeScenarios[$index - 1] ?? null;
                         $targetBotId = $scenario['target_bot_id'] ?? null;
                         
                         if ($targetBotId) {
                             $targetBot = \App\Models\AiBot::find($targetBotId);
                             if ($targetBot) {
                                // 1. Close Current Session
                                \App\Models\BotSession::where('conversation_id', $conversation->id)
                                    ->where('bot_id', $this->id)
                                    ->whereNull('ended_at')
                                    ->update(['ended_at' => now()]);

                                // 2. Prepare Context (History)
                                // Fetch recent messages (last 10), excluding the very latest user message which triggers the response
                                $recentMessages = $conversation->messages()
                                    ->orderBy('created_at', 'desc')
                                    ->take(11) // Take 11 to allow removing the latest one
                                    ->get()
                                    ->reverse()
                                    ->values();

                                // Identify Trigger Message (Last Inbound)
                                $triggerMessage = $recentMessages->where('direction', 'inbound')->last();
                                
                                $threadMessages = [];
                                foreach ($recentMessages as $msg) {
                                    // Skip the trigger message from history population
                                    if ($triggerMessage && $msg->id === $triggerMessage->id) continue;
                                    
                                    $role = ($msg->direction === 'inbound') ? 'user' : 'assistant';
                                    
                                    // Only include text messages? (Assuming type=text for now)
                                    $content = $msg->content ?? '';
                                    if (empty($content)) continue;

                                    $threadMessages[] = [
                                        'role' => $role,
                                        'content' => $content,
                                    ];
                                }
                                
                                // 3. Create OpenAI Thread with Context
                                $targetApiKey = $targetBot->tenant->openai_key ?? $targetBot->tenant->settings['openai_key'] ?? null;
                                $newThreadId = null;

                                if ($targetApiKey && !empty($threadMessages)) {
                                    try {
                                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                                            'Authorization' => 'Bearer ' . $targetApiKey,
                                            'OpenAI-Beta' => 'assistants=v2',
                                            'Content-Type' => 'application/json',
                                        ])->post('https://api.openai.com/v1/threads', [
                                            'messages' => $threadMessages
                                        ]);
                                        
                                        if ($response->successful()) {
                                            $newThreadId = $response->json()['id'];
                                        } else {
                                             \Log::error("Transfer Context: Failed to create thread. " . $response->body());
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error("Transfer Context: Exception " . $e->getMessage());
                                    }
                                }

                                // 4. Create New Session
                                $newSession = \App\Models\BotSession::create([
                                    'tenant_id' => $this->tenant_id,
                                    'bot_id' => $targetBot->id,
                                    'conversation_id' => $conversation->id,
                                    'started_at' => now(),
                                    'channel' => $conversation->channel->type ?? 'unknown',
                                    'thread_id' => $newThreadId
                                ]);

                                // 5. Apply Contact Tag if Configured
                                if (!empty($scenario['add_contact_tag_id']) && $conversation->contact) {
                                    $conversation->contact->tags()->syncWithoutDetaching([$scenario['add_contact_tag_id']]);
                                }

                                // 6. Trigger Target Bot Response (Seamless Handover)
                                if ($triggerMessage) {
                                    try {
                                        $targetBot->processBotResponse($triggerMessage);
                                        $output = ['status' => 'success', 'message' => "Transferred to {$targetBot->name}. Context passed."];
                                    } catch (\Exception $e) {
                                        \Log::error("Transfer Execution Failed: " . $e->getMessage());
                                        $output = ['status' => 'success', 'message' => "Transferred to {$targetBot->name}, but failed to auto-reply."];
                                    }
                                } else {
                                     $output = ['status' => 'success', 'message' => "Transferred to {$targetBot->name}. No trigger message found."];
                                }
                                
                                $transferred = true;
                            }
                         }
                    }
                    
                    if (!$transferred) {
                        $output = ['status' => 'error', 'message' => "Transfer configuration not found."];
                    }

                } elseif ($functionName === 'close_conversation') {
                     $conversation->update(['status' => 'closed']);
                     if ($conversation->activeBotSession) {
                         $conversation->activeBotSession->update(['ended_at' => now()]);
                     }
                     $output = ['status' => 'success', 'message' => 'Conversation closed.'];

                } elseif ($functionName === 'mark_conversation_busy') {
                     $conversation->update(['status' => 'busy']);
                     $output = ['status' => 'success', 'message' => 'Conversation marked as busy.'];

                } elseif ($functionName === 'snooze_conversation') {
                     $hours = $args['duration_hours'] ?? 24;
                     $conversation->update([
                         'status' => 'snoozed',
                         'next_followup_at' => now()->addHours($hours)
                     ]);
                     $output = ['status' => 'success', 'message' => "Conversation snoozed for {$hours} hours."];

                } elseif ($functionName === 'enable_dnd') {
                     // Enable Do Not Disturb for the contact
                     if (!$conversation || !$conversation->contact) {
                         $output = ['status' => 'error', 'message' => 'No contact associated with this conversation.'];
                     } else {
                         $contact = $conversation->contact;
                         $dndType = $args['type'] ?? 'text'; // Default to text/SMS
                         
                         $updateData = [];
                         $acknowledgeMessage = '';
                         
                         // Enable the requested DND type
                         switch ($dndType) {
                             case 'text':
                             case 'sms':
                                 $updateData['dnd_text'] = true;
                                 $acknowledgeMessage = 'You will no longer receive text messages from us.';
                                 break;
                             
                             case 'email':
                                 $updateData['dnd_email'] = true;
                                 $acknowledgeMessage = 'You will no longer receive emails from us.';
                                 break;
                             
                             case 'calls':
                                 $updateData['dnd_calls'] = true;
                                 $acknowledgeMessage = 'You will no longer receive calls from us.';
                                 break;
                             
                             case 'all':
                                 $updateData['dnd_text'] = true;
                                 $updateData['dnd_email'] = true;
                                 $updateData['dnd_calls'] = true;
                                 $acknowledgeMessage = 'You will no longer receive any marketing communications from us.';
                                 break;
                             
                             default:
                                 $updateData['dnd_text'] = true;
                                 $acknowledgeMessage = 'You will no longer receive text messages from us.';
                         }
                         
                         // Update contact DND preferences
                         $contact->update($updateData);
                         
                         \Log::info("DND Enabled for Contact {$contact->id}", [
                             'type' => $dndType,
                             'updated_fields' => array_keys($updateData)
                         ]);
                         
                         $output = [
                             'status' => 'success',
                             'message' => $acknowledgeMessage,
                             'acknowledge_to_user' => true
                         ];
                     }

                } else {
                    // Check for Update Contact Actions (Dynamic Names)
                    // The tool name is generated as strtolower(str_replace(' ', '_', $item['action_name']))
                    $updateContactAction = $this->actions->where('action_name', 'Update Contact')->first();
                    $matchedUpdate = false;
                    
                    if ($updateContactAction) {
                        $config = $updateContactAction->config_json;
                        $items = $config['items'] ?? [];
                        
                        // Legacy single item support
                        if (empty($items) && !empty($config['field_id'])) {
                            $items[] = [
                                'action_name' => $updateContactAction->action_name,
                                'field_id' => $config['field_id']
                            ];
                        }

                        foreach ($items as $item) {
                            $expectedToolName = strtolower(str_replace(' ', '_', $item['action_name']));
                            if ($functionName === $expectedToolName) {
                                // Found the matching action item
                                $matchedUpdate = true;
                                $fieldId = $item['field_id'];
                                $value = $args['value'] ?? null;
                                
                                if (!$conversation || !$conversation->contact) {
                                    $output = ['status' => 'error', 'message' => 'No contact associated with this conversation.'];
                                    break;
                                }
                                
                                $contact = $conversation->contact;
                                $updatedField = $fieldId;

                                // 1. Standard Fields
                                $standardFields = [
                                    'name', 'first_name', 'last_name', 'email', 'phone', 
                                    'gender', 'date_of_birth', 'company_name', 'salutation'
                                ]; // Add more as needed
                                
                                // Map friendly names or IDs to actual DB columns if needed
                                // Assuming field_id IS the column name for standard fields, or a numeric ID for custom fields.
                                
                                if (in_array($fieldId, $standardFields) || $fieldId === 'emails' || $fieldId === 'phones') {
                                    if ($fieldId === 'email' || $fieldId === 'emails') {
                                        // Update Primary Email (Append or replace?)
                                        // Simple: Prepend to make primary
                                        $emails = $contact->emails ?? [];
                                        if (!is_array($emails)) $emails = [];
                                        array_unshift($emails, ['value' => $value, 'type' => 'work']);
                                        $contact->emails = $emails;
                                        $updatedField = 'email';
                                    } elseif ($fieldId === 'phone' || $fieldId === 'phones') {
                                        $phones = $contact->phones ?? [];
                                        if (!is_array($phones)) $phones = [];
                                        array_unshift($phones, ['value' => $value, 'type' => 'mobile']);
                                        $contact->phones = $phones;
                                        $updatedField = 'phone';
                                    } elseif ($fieldId === 'company_name') {
                                        // Update company name (requires Company model or text field on contact? Contact has company_id)
                                        // For now ignore or handle if we have a text field. 
                                        // Migration showed `company_id`. We'd need to find/create company.
                                        // Let's skip complex relation updates for now unless requested.
                                        $output = ['status' => 'error', 'message' => "Updating Company Name not yet supported directly."];
                                        break; 
                                    } else {
                                        // Direct update
                                        $contact->$fieldId = $value;
                                    }
                                    $contact->save();
                                    
                                } elseif (is_numeric($fieldId)) {
                                    // 2. Custom Field (by ID)
                                    $customField = \App\Models\CustomField::find($fieldId);
                                    if ($customField) {
                                        // Update CustomFieldValue
                                        \App\Models\CustomFieldValue::updateOrCreate(
                                            [
                                                'field_id' => $customField->id,
                                                'record_id' => $contact->id,
                                                'object_id' => $customField->object_id
                                            ],
                                            [
                                                'value_text' => $value 
                                            ]
                                        );
                                        $updatedField = $customField->name;
                                    } else {
                                        $output = ['status' => 'error', 'message' => "Custom field definition not found for ID {$fieldId}."];
                                        break;
                                    }
                                } else {
                                    // 3. Fallback: Meta
                                    $meta = $contact->meta ?? [];
                                    $meta[$fieldId] = $value;
                                    $contact->meta = $meta;
                                    $contact->save();
                                }
                                
                                $output = ['status' => 'success', 'message' => "Updated {$updatedField} to '{$value}'."];
                                break;
                            }
                        }
                    }
                    
                    if (!$matchedUpdate) {
                         // Unknown tool
                       $output = ['status' => 'error', 'message' => "Tool '$functionName' not found or not configured."];
                    }
                }
                
            } catch (\Exception $e) {
                $output = ['status' => 'error', 'message' => $e->getMessage()];
            }

            $outputs[] = [
                'tool_call_id' => $call['id'],
                'output' => json_encode($output),
            ];
        }
        return $outputs;
    }
    protected function scheduleNextFollowup($conversation)
    {
        // 1. Check if Bot has Auto Follow-up Action
        $action = $this->actions->where('action_name', 'Auto Follow-up')
            ->where('is_active', true)
            ->first();
            
        if (!$action) return;

        $rawScenarios = $action->config_json['scenarios'] ?? [];
        $delays = [];
        
        foreach ($rawScenarios as $s) {
            if (!empty($s['sequences']) && is_array($s['sequences'])) {
                foreach ($s['sequences'] as $seq) {
                    if (isset($seq['delay_minutes'])) {
                         $delays[] = (int)$seq['delay_minutes'];
                    }
                }
            } elseif (isset($s['delay']) || isset($s['delay_minutes'])) {
                $delays[] = (int)($s['delay'] ?? $s['delay_minutes'] ?? 0);
            }
        }

        $delays = array_filter($delays, fn($d) => is_numeric($d) && $d > 0);
        
        if (empty($delays)) return;
        
        $minDelay = min($delays); // in minutes
        
        $conversation->update([
            'next_followup_at' => now()->addMinutes($minDelay)
        ]);
    }

    /**
     * Check and trigger automatic workflows based on events
     * 
     * @param \App\Models\Conversation $conversation
     * @param string $event
     * @return void
     */
    protected function checkAndTriggerAutomaticWorkflows($conversation, $event = 'conversation_start')
    {
        \Log::info("checkAndTriggerAutomaticWorkflows check", ['event' => $event, 'bot_id' => $this->id]);
        // Find "Trigger Workflow" action
        $action = $this->actions->where('action_name', 'Trigger Workflow')->first();
        
        if (!$action || !$conversation || !$conversation->contact) {
            if (!$action) {
                \Log::info("No Trigger Workflow action found for bot", ['bot_id' => $this->id]);
            } else {
                \Log::info("Missing conversation or contact for workflow trigger", ['conversation_id' => $conversation->id ?? null, 'contact_id' => $conversation->contact->id ?? null]);
            }
            return;
        }
        
        $config = $action->config_json;
        $triggerType = $config['trigger_type'] ?? 'automatic'; // Default to AI-decided for backward compatibility
        
        \Log::info("Trigger config found", ['trigger_type' => $triggerType, 'config' => $config]);

        // Only process automatic triggers
        if ($triggerType !== 'automatic') {
            \Log::info("Trigger type is not automatic, skipping", ['bot_id' => $this->id]);
            return;
        }
        
        $triggerEvent = $config['trigger_event'] ?? 'conversation_start';
        $pipelineId = $config['pipeline_id'] ?? null;
        
        // Check if the event matches (allow first_message as alias for conversation_start)
        $isMatch = ($triggerEvent === $event) || ($event === 'conversation_start' && $triggerEvent === 'first_message');

        \Log::info("Trigger match check", ['isMatch' => $isMatch, 'triggerEvent' => $triggerEvent, 'incomingEvent' => $event, 'pipelineId' => $pipelineId]);

        if ($isMatch && $pipelineId) {
            // Additional check for conversation_start/first_message: 
            // Only trigger if no opportunity already exists for this conversation
            if ($event === 'conversation_start' || $event === 'first_message') {
                $hasOpportunity = \App\Models\Opportunity::where('conversation_id', $conversation->id)->exists();
                \Log::info("Opportunity check for conversation_start", ['hasOpportunity' => $hasOpportunity, 'conversation_id' => $conversation->id]);
                
                if ($hasOpportunity) {
                    \Log::info("Opportunity already exists, skipping workflow trigger", ['bot_id' => $this->id, 'conversation_id' => $conversation->id]);
                    return; 
                }
            }
            
            $this->triggerWorkflow($conversation, $pipelineId);
        }
    }

    /**
     * Trigger a workflow by creating an opportunity in the specified pipeline
     * 
     * @param \App\Models\Conversation $conversation
     * @param int $pipelineId
     * @return void
     */
    protected function triggerWorkflow($conversation, $pipelineId)
    {
        $pipeline = \App\Models\Pipeline::find($pipelineId);
        
        if (!$pipeline) {
            Log::warning("Pipeline not found for workflow trigger", ['pipeline_id' => $pipelineId]);
            return;
        }
        
        $firstStage = $pipeline->stages()->orderBy('order_index')->first();
        
        if (!$firstStage) {
            Log::warning("Pipeline has no stages", ['pipeline_id' => $pipelineId, 'pipeline_name' => $pipeline->name]);
            return;
        }
        
        // Check if opportunity already exists for this contact in this pipeline
        $existingOpp = \App\Models\Opportunity::where('contact_id', $conversation->contact->id)
            ->where('pipeline_id', $pipeline->id)
            ->where('status', 'open')
            ->first();
        
        if ($existingOpp) {
            Log::info("Opportunity already exists for contact in pipeline", [
                'contact_id' => $conversation->contact->id,
                'pipeline_id' => $pipeline->id,
                'opportunity_id' => $existingOpp->id
            ]);
            return;
        }
        
        // Create Opportunity
        $opp = \App\Models\Opportunity::create([
            'tenant_id' => $this->tenant_id,
            'name' => $conversation->contact->name ?? 'New Lead',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $firstStage->id,
            'contact_id' => $conversation->contact->id,
            'status' => 'open',
            'source' => 'chat',
            'owner_id' => $conversation->assigned_to ?? null,
            'expected_close_date' => now()->addDays(7),
        ]);

        // Add Audit Note
        Note::create([
            'tenant_id' => $this->tenant_id,
            'object_type' => \App\Models\Opportunity::class,
            'object_id' => $opp->id,
            'content' => "Opportunity created automatically via AI Bot 'Automatic Workflow' (Conversation started).",
            'source' => 'automation',
        ]);
        
        Log::info("Automatic workflow triggered", [
            'opportunity_id' => $opp->id,
            'opportunity_name' => $opp->name,
            'pipeline' => $pipeline->name,
            'contact' => $conversation->contact->name,
            'conversation_id' => $conversation->id
        ]);

        // Link contact to this pipeline/stage if not already assigned
        $contact = $conversation->contact;
        if (!$contact->pipeline_id) {
            Log::info("Linking contact to pipeline and stage", ['contact_id' => $contact->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $firstStage->id]);
            $contact->update([
                'pipeline_id' => $pipeline->id,
                'stage_id' => $firstStage->id,
                'stage_updated_at' => now(),
                'stage_updated_by' => 'workflow'
            ]);
        }

        // If pipeline has AI sorting enabled, dispatch classification job
        if ($pipeline->ai_sorting_enabled) {
            Log::info("Dispatching AI classification for new opportunity", ['conversation_id' => $conversation->id]);
            \App\Jobs\Pipelines\ProcessMessageClassification::dispatch($conversation->id);
        }
    }
}
