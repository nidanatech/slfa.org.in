<?php

namespace App\Services;

use App\Models\AiBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    /**
     * Handle an incoming message and generate a response.
     *
     * @param string $message
     * @param AiBot $bot
     * @return string
     */
    public function handleMessage(string $message, AiBot $bot)
    {
        // Wrapper for generateText using bot configuration if needed
        $systemPrompt = $bot->prompt ?? "You are a helpful assistant.";
        return $this->generateText($message, $systemPrompt);
    }

    public function generateText($prompt, $systemPrompt, $maxTokens = 1000, $temperature = 0.7)
    {
        // Check Token Quota for Tenant
        $tenant = auth()->user()?->tenant; // Default to current user's tenant if not explicitly provided
        if ($tenant && !$tenant->canCreate('tokens_monthly')) {
            Log::warning("Token quota exceeded for tenant: " . $tenant->id);
            throw new \Exception("AI Quota Exceeded: This account has reached its monthly token limit.");
        }

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o', // Default model
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => (int) $maxTokens,
                'temperature' => (float) $temperature,
            ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'] ?? '';
        }

        Log::error('OpenAI Generation Failed', ['body' => $response->body()]);
        throw new \Exception('Failed to generate content: ' . $response->body());
    }

    public function transcribeFromPath(string $path)
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found at path: {$path}");
        }

        $response = Http::withToken($this->apiKey)
            ->attach('file', file_get_contents($path), basename($path))
            ->post("{$this->baseUrl}/audio/transcriptions", [
                'model' => 'whisper-1',
                'language' => 'en',
            ]);

        if ($response->successful()) {
            return $response->json()['text'] ?? '';
        }

        Log::error('OpenAI Transcription Failed', ['body' => $response->body()]);
        throw new \Exception('Failed to transcribe audio: ' . $response->body());
    }
}
