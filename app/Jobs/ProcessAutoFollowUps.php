<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAutoFollowUps implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conversations = \App\Models\Conversation::where('status', 'open')
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now())
            ->whereHas('activeBotSession', function($q) {
                $q->whereNull('ended_at'); // Ensure session is active
            })
            ->with(['activeBotSession.bot.actions', 'contact'])
            ->get();

        foreach ($conversations as $conversation) {
            $this->processFollowUp($conversation);
        }
    }

    protected function processFollowUp($conversation)
    {
        $bot = $conversation->activeBotSession->bot ?? null;
        if (!$bot) return;

        // Double check status hasn't changed to closed/busy/snoozed in the background
        // Refresh from DB to be absolutely sure
        $conversation->refresh();
        if ($conversation->status !== 'open') {
             \Log::info("ProcessAutoFollowUps: Skipping Conversation {$conversation->id} because status is '{$conversation->status}'");
             $conversation->update(['next_followup_at' => null]);
             return;
        }

        $action = $bot->actions->where('action_name', 'Auto Follow-up')
            ->where('is_active', true)
            ->first();

        if (!$action) {
            // No action config, clear schedule
            $conversation->update(['next_followup_at' => null]);
            return;
        }

        $config = $action->config_json ?? [];
        $followupLimit = $config['followup_limit'] ?? 0;
        $avoidSpam = $config['avoid_spam'] ?? false;
        $currentCount = $conversation->automated_followup_count ?? 0;

        // 1. Check Follow-up Limit
        if ($followupLimit > 0 && $currentCount >= $followupLimit) {
            \Log::info("ProcessAutoFollowUps: Follow-up limit reached for Conversation {$conversation->id} ($currentCount >= $followupLimit)");
            $conversation->update([
                'next_followup_at' => null,
                'status' => 'closed'
            ]);
            
            if ($conversation->activeBotSession) {
                $conversation->activeBotSession->update(['ended_at' => now()]);
            }
            return;
        }

        // 2. Spam Avoidance Logic
        if ($avoidSpam) {
            // If we've already sent at least one follow-up, and there's still no reply, 
            // maybe we should be more conservative.
            // For now, if avoidSpam is on, we skip if the count is already significant (e.g. > 3) 
            // or if it's been more than a week since last user message.
            if ($currentCount >= 3) {
                 \Log::info("ProcessAutoFollowUps: Spam avoidance triggered for Conversation {$conversation->id} (Count: $currentCount)");
                 $conversation->update(['next_followup_at' => null]);
                 return;
            }
            
            $lastMessageAt = $conversation->last_message_at ?? $conversation->created_at;
            if ($lastMessageAt->diffInDays(now()) > 7) {
                 \Log::info("ProcessAutoFollowUps: Spam avoidance triggered for Conversation {$conversation->id} (Last message > 7 days ago)");
                 $conversation->update(['next_followup_at' => null]);
                 return;
            }
        }

        $rawScenarios = $config['scenarios'] ?? [];
        $scenarios = [];
        
        // Flatten nested sequences from Frontend builder
        foreach ($rawScenarios as $s) {
            if (!empty($s['sequences']) && is_array($s['sequences'])) {
                foreach ($s['sequences'] as $seq) {
                    if (isset($seq['delay_minutes']) && isset($seq['message_content'])) {
                        $scenarios[] = [
                            'delay' => (int)$seq['delay_minutes'],
                            'message' => $seq['message_content'],
                            'source' => $seq['message_source'] ?? 'static'
                        ];
                    }
                }
            } elseif (isset($s['delay']) || isset($s['delay_minutes'])) {
                // Fallback for flat structure
                $scenarios[] = [
                    'delay' => (int)($s['delay'] ?? $s['delay_minutes'] ?? 0),
                    'message' => $s['message'] ?? $s['message_content'] ?? '',
                    'source' => $s['source'] ?? 'static'
                ];
            }
        }

        if (empty($scenarios)) return;

        // Sort scenarios by delay (minutes)
        // usage: delay in minutes. 
        // We need to determine WHICH scenario matches the current time relative to last_message_at
        $lastMessageAt = $conversation->last_message_at ?? $conversation->created_at;
        $minutesSinceLastMessage = $lastMessageAt->diffInMinutes(now());

        // Find the scenario that *should* have triggered roughly now
        // Because next_followup_at was set, we assume it corresponds to a specific step.
        // But to be stateless/robust, we check which step fits "minutesSinceLastMessage".
        
        $triggeredScenario = null;
        $nextDelay = null;

        // Sort scenarios ascending
        usort($scenarios, function($a, $b) {
            return ($a['delay'] ?? 0) <=> ($b['delay'] ?? 0);
        });

        foreach ($scenarios as $scenario) {
            $delay = $scenario['delay'] ?? 0;
            if ($minutesSinceLastMessage >= $delay) {
                // This scenario is due or past due.
                // Check if we already processed it? 
                // We rely on last_automated_followup_at. 
                // Ideally we track specific step index. 
                // MVP: If triggers, we assume it's the one we scheduled.
                $triggeredScenario = $scenario;
            } else {
                // This scenario is in the future. This is our "Next" step.
                $nextDelay = $delay;
                break; 
            }
        }
        
        if ($triggeredScenario) {
            // Execute Message
            $messageContent = $triggeredScenario['message'] ?? '';
            $source = $triggeredScenario['source'] ?? 'static';

            if ($source === 'ai' && !empty($messageContent)) {
                 \Log::info("ProcessAutoFollowUps: Generating AI follow-up for Conversation {$conversation->id}");
                 $messageContent = $this->generateAiFollowup($bot, $conversation, $messageContent);
            }

            if (!empty($messageContent)) {
                 $this->sendMessageAsBot($bot, $conversation, $messageContent);
            }
            
            // Mark processed
            $conversation->update([
                'last_automated_followup_at' => now(),
                'automated_followup_count' => $currentCount + 1,
                 // Schedule next if available and limit allows
                'next_followup_at' => ($nextDelay && ($followupLimit == 0 || ($currentCount + 1) < $followupLimit)) 
                    ? $lastMessageAt->copy()->addMinutes($nextDelay) 
                    : null
            ]);
            
            // Log
            \Illuminate\Support\Facades\Log::info("ProcessAutoFollowUps: Sent follow-up for Conversation {$conversation->id}. Total count: " . ($currentCount + 1));
        } else {
            // Something weird, maybe reset?
            $conversation->update(['next_followup_at' => null]);
        }
    }

    protected function generateAiFollowup($bot, $conversation, $instruction)
    {
        $session = $conversation->activeBotSession;
        if (!$session || !$session->thread_id) return $instruction;

        $apiKey = $bot->tenant->openai_key ?? $bot->tenant->settings['openai_key'] ?? config('services.openai.key');
        if (!$apiKey) return $instruction;

        $baseUrl = 'https://api.openai.com/v1';
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];

        // 1. Add Instruction Message to Thread
        // We tell the AI what to do.
        $prompt = "### CONTEXT-AWARE FOLLOW-UP INSTRUCTION ###\n" .
                  "The contact has not replied recently. Based on the conversation history above, please generate a natural, helpful, and non-pushy follow-up message.\n" .
                  "GOAL: \"{$instruction}\"\n" .
                  "REQUIREMENTS:\n" .
                  "- Use the conversation context to make the message sound personal and relevant.\n" .
                  "- DO NOT be aggressive or pushy.\n" .
                  "- If the conversation context suggests the user is done or not interested, be extremely polite or ask if they need anything else.\n" .
                  "- Return ONLY the message text. No prefixes like 'Agent:' or 'Follow-up:'.";
        
        try {
            \Illuminate\Support\Facades\Http::withHeaders($headers)->post("$baseUrl/threads/{$session->thread_id}/messages", [
                'role' => 'user',
                'content' => $prompt,
            ]);

            // 2. Run Assistant
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)->post("$baseUrl/threads/{$session->thread_id}/runs", [
                'assistant_id' => $bot->assistant_id,
            ]);

            if (!$response->successful()) {
                \Log::error("ProcessAutoFollowUps AI: Failed to start run. " . $response->body());
                return $instruction;
            }

            $runId = $response->json()['id'];
            $status = 'queued';
            $attempts = 0;
            
            // Wait for result (briefly, max 15s)
            while (in_array($status, ['queued', 'in_progress']) && $attempts < 15) {
                sleep(1);
                $attempts++;
                $check = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/threads/{$session->thread_id}/runs/$runId");
                $status = $check->json()['status'] ?? 'failed';
            }

            if ($status === 'completed') {
                $msgResp = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/threads/{$session->thread_id}/messages?limit=1");
                $data = $msgResp->json()['data'] ?? [];
                if (!empty($data) && $data[0]['role'] === 'assistant') {
                     $blocks = $data[0]['content'] ?? [];
                     $content = '';
                     foreach($blocks as $block) {
                         if ($block['type'] === 'text') $content .= $block['text']['value'];
                     }
                     return !empty($content) ? trim($content) : $instruction;
                }
            } else {
                \Log::warning("ProcessAutoFollowUps AI: Run did not complete. Status: {$status}");
            }
        } catch (\Exception $e) {
            \Log::error("ProcessAutoFollowUps AI: Exception: " . $e->getMessage());
        }

        return $instruction; // Fallback to static instruction if AI fails
    }

    protected function sendMessageAsBot($bot, $conversation, $content)
    {
        // 1. Add to Database
        $message = $conversation->messages()->create([
            'direction' => 'outbound', // Bot
            'content' => $content,
            'type' => 'text',
            'sender_type' => 'bot',
            'status' => 'pending',
        ]);

        // 2. Dispatch to External Platform
        if (method_exists($bot, 'dispatchOutboundMessage')) {
            $delivered = $bot->dispatchOutboundMessage($message, $conversation);
            if ($delivered) {
                $message->update(['status' => 'sent']);
            } else {
                $message->update(['status' => 'failed']);
                \Log::error("ProcessAutoFollowUps: Delivery failed for message {$message->id}");
            }
        }

        // 3. Add to OpenAI Thread (to keep context sync)
        if ($conversation->activeBotSession && $conversation->activeBotSession->thread_id) {
             $apiKey = $bot->tenant->openai_key ?? $bot->tenant->settings['openai_key'] ?? config('services.openai.key');
             if ($apiKey) {
                 try {
                     \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'OpenAI-Beta' => 'assistants=v2',
                        'Content-Type' => 'application/json',
                    ])->post("https://api.openai.com/v1/threads/{$conversation->activeBotSession->thread_id}/messages", [
                        'role' => 'assistant',
                        'content' => $content,
                    ]);
                 } catch (\Exception $e) {
                     \Illuminate\Support\Facades\Log::error("ProcessAutoFollowUps: Failed to sync thread. " . $e->getMessage());
                 }
             }
        }
    }
}
