<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LlmUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function generateEmailTemplate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'category' => 'nullable|string',
        ]);

        $tenant = auth()->user()->tenant;
        $openaiKey = $tenant->openai_api_key ?? env('OPENAI_API_KEY');

        if (!$openaiKey) {
            return response()->json([
                'error' => 'OpenAI API key not configured. Please add it in Settings.',
                'success' => false,
            ], 400);
        }

        $startTime = microtime(true);

        try {
            $systemPrompt = 'You are an expert email marketing copywriter. Generate professional, engaging HTML email templates. Use modern HTML email best practices. Include personalization tags like {{contact.name}}, {{contact.email}}, {{contact.first_name}} where appropriate. Return only the HTML code without explanations.';
            
            $userPrompt = "Create an HTML email template for: {$validated['prompt']}" . 
                         ($validated['category'] ? " Category: {$validated['category']}" : "");

            // Make API call to OpenAI
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            $duration = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();
                $html = $data['choices'][0]['message']['content'] ?? '';
                
                // Extract usage data
                $usage = $data['usage'] ?? [];
                $promptTokens = $usage['prompt_tokens'] ?? 0;
                $completionTokens = $usage['completion_tokens'] ?? 0;
                $totalTokens = $usage['total_tokens'] ?? 0;

                // Log usage in llm_usage table
                LlmUsage::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'model' => 'gpt-4',
                    'provider' => 'openai',
                    'feature' => 'email_template_generation',
                    'prompt' => $userPrompt,
                    'response' => $html,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $totalTokens,
                    'cost' => $this->calculateCost('gpt-4', $promptTokens, $completionTokens),
                    'duration' => $duration,
                    'status' => 'success',
                ]);
                
                // Clean up the response (remove markdown code blocks if present)
                $html = preg_replace('/```html\n?/', '', $html);
                $html = preg_replace('/```\n?/', '', $html);
                $html = trim($html);

                // Extract subject from HTML if present in title or h1
                $subject = '';
                if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
                    $subject = $matches[1];
                } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $matches)) {
                    $subject = strip_tags($matches[1]);
                }

                return response()->json([
                    'html' => $html,
                    'subject' => $subject,
                    'success' => true,
                ]);
            }

            // Log failed request
            LlmUsage::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'model' => 'gpt-4',
                'provider' => 'openai',
                'feature' => 'email_template_generation',
                'prompt' => $userPrompt,
                'response' => $response->body(),
                'status' => 'failed',
                'error_message' => 'API request failed',
                'duration' => $duration,
            ]);

            return response()->json([
                'error' => 'Failed to generate template',
                'success' => false,
            ], 500);

        } catch (\Exception $e) {
            // Log error
            LlmUsage::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'model' => 'gpt-4',
                'provider' => 'openai',
                'feature' => 'email_template_generation',
                'prompt' => $validated['prompt'],
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'duration' => microtime(true) - $startTime,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    /**
     * Calculate cost based on OpenAI pricing
     * GPT-4: $0.03 per 1K prompt tokens, $0.06 per 1K completion tokens
     */
    private function calculateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $pricing = [
            'gpt-4' => ['prompt' => 0.03, 'completion' => 0.06],
            'gpt-3.5-turbo' => ['prompt' => 0.0015, 'completion' => 0.002],
        ];

        $rates = $pricing[$model] ?? $pricing['gpt-4'];
        
        $promptCost = ($promptTokens / 1000) * $rates['prompt'];
        $completionCost = ($completionTokens / 1000) * $rates['completion'];
        
        return round($promptCost + $completionCost, 6);
    }
    /**
     * Analyze sentiment of vendor review
     */
    public function analyzeVendorSentiment(Request $request)
    {
        $validated = $request->validate([
            'review_text' => 'required|string',
        ]);

        $tenant = auth()->user()->tenant;
        $openaiKey = $tenant->openai_api_key ?? env('OPENAI_API_KEY');

        if (!$openaiKey) {
            return response()->json([
                'error' => 'OpenAI API key not configured.',
                'success' => false,
            ], 400);
        }

        $startTime = microtime(true);

        try {
            $systemPrompt = 'You are an expert sentiment analyzer. Analyze the following vendor review text. Return ONLY a JSON object with two keys: "score" (float between -1.0 for very negative and 1.0 for very positive) and "label" (string: "positive", "neutral", or "negative").';
            
            $userPrompt = $validated['review_text'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 100,
            ]);

            $duration = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '{}';
                
                // Parse JSON from content
                $analysis = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Fallback basic parsing if JSON is malformed
                    $score = 0;
                    $label = 'neutral';
                } else {
                    $score = $analysis['score'] ?? 0;
                    $label = $analysis['label'] ?? 'neutral';
                }

                // Log usage
                $usage = $data['usage'] ?? [];
                $promptTokens = $usage['prompt_tokens'] ?? 0;
                $completionTokens = $usage['completion_tokens'] ?? 0;
                $totalTokens = $usage['total_tokens'] ?? 0;

                LlmUsage::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'model' => 'gpt-4',
                    'provider' => 'openai',
                    'feature' => 'vendor_review_sentiment',
                    'prompt' => $userPrompt,
                    'response' => $content,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $totalTokens,
                    'cost' => $this->calculateCost('gpt-4', $promptTokens, $completionTokens),
                    'duration' => $duration,
                    'status' => 'success',
                ]);

                return response()->json([
                    'success' => true,
                    'sentiment_score' => $score,
                    'sentiment_label' => $label,
                ]);
            }

            return response()->json(['error' => 'AI request failed'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
