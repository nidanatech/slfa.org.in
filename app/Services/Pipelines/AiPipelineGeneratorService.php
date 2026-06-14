<?php

namespace App\Services\Pipelines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPipelineGeneratorService
{
    public function generateFromPrompt(string $prompt, string $tenantOpenAiKey = null): ?array
    {
        if (empty($tenantOpenAiKey)) {
            // Check config if not provided
             // For now we rely on the controller passing the key
             return null;
        }

        try {
            $systemPrompt = "You are an expert Sales Operations Consultant. Create a structured pipeline based on the user's description.
Output valid JSON with:
- name: Suggested pipeline name
- description: Short description
- stages: Array of objects { name, stage_type (open/won/lost), probability (0-100), description, color (hex) }

Ensure colors are distinct and professional. Typically 3-7 stages. Always include at least one 'won' and one 'lost' stage type (or assume generic 'Closed Won'/'Closed Lost' if not implied).";

            $response = Http::withToken($tenantOpenAiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->failed()) {
                Log::error("AI Pipeline Generation Error: " . $response->body());
                return null;
            }

            $content = $response->json('choices.0.message.content');
            return json_decode($content, true);

        } catch (\Exception $e) {
            Log::error("AI Pipeline Generation Exception: " . $e->getMessage());
            return null;
        }
    }
}
