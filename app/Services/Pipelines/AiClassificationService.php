<?php

namespace App\Services\Pipelines;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
// Assuming some AI client exists or just stubbing the logic for now
// use App\Services\Ai\AiClient; 

class AiClassificationService
{
    public function classify(Conversation $conversation): ?array
    {
        try {
            // 1. Get Tenant and API Key
            $tenant = $conversation->tenant;
            if (!$tenant || empty($tenant->openai_key)) {
                Log::warning("AI Classification skipped: No OpenAI key found for tenant {$conversation->tenant_id}");
                return null;
            }

            // 2. Fetch recent messages
            $messages = $conversation->messages()
                ->latest()
                ->take(15) // Context window
                ->get()
                ->reverse() // chronological order
                ->values();

            if ($messages->isEmpty()) {
                return null;
            }

            // 3. Construct System Prompt
            $systemPrompt = "You are an AI assistant for a CRM pipeline. Your goal is to analyze the conversation and extract structured data to route the contact to the correct stage.
            
Output strictly in valid JSON format with the following keys:
- interest_level (none, low, medium, high)
- intent_type (inquiry, pricing, booking, negotiation, complaint, spam, other)
- urgency (low, medium, high)
- qualification (unqualified, partially_qualified, qualified)
- sentiment (positive, neutral, negative)
- confidence (float 0.0 to 1.0)
- reason (short explanation)

Do not include any markdown formatting or explanations outside the JSON.";

            // 4. Construct Conversation History for AI
            $conversationHistory = [];
            foreach ($messages as $msg) {
                $role = $msg->direction === 'inbound' ? 'user' : 'assistant';
                $conversationHistory[] = [
                    'role' => $role,
                    'content' => $msg->content ?? '[Attachment/Empty]'
                ];
            }

            // 5. Call OpenAI API
            $response = \Illuminate\Support\Facades\Http::withToken($tenant->openai_key)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $conversationHistory
                    ),
                    'temperature' => 0.0,
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->failed()) {
                Log::error("OpenAI API Error for conversation {$conversation->id}: " . $response->body());
                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return null;
            }

            // 6. Decode and Validate JSON
            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("AI JSON Parse Error: " . json_last_error_msg());
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("AI Classification failed for conversation {$conversation->id}: " . $e->getMessage());
            return null;
        }
    }
}
