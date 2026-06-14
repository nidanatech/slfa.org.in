<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Note;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InboxAssistantService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Generate a professional email reply based on a suggestion and context.
     *
     * @param Conversation $conversation
     * @param string $suggestion
     * @return string
     */
    public function generateEmailReply(Conversation $conversation, string $suggestion)
    {
        $tenant = $conversation->tenant;
        $apiKey = $tenant->openai_key ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception("OpenAI API Key not configured.");
        }

        $contact = $conversation->contact;
        $notes = $contact->notes()->latest()->limit(5)->get();
        $messages = $conversation->messages()->latest()->limit(10)->get()->reverse();

        // Build context string
        $context = "BUSINESS CONTEXT:\n";
        $profile = $tenant->businessProfile;
        $context .= "- Business Name: " . ($profile?->business_name ?? $tenant->name) . "\n";
        $context .= "- Business Type: " . ($profile?->business_type ?? 'Service Provider') . "\n\n";

        $context .= "CONTACT INFO:\n";
        $context .= "- Name: " . $contact->name . "\n";
        
        if ($notes->count() > 0) {
            $context .= "\nINTERNAL NOTES ABOUT CONTACT:\n";
            foreach ($notes as $note) {
                $context .= "- " . $note->content . "\n";
            }
        }

        $context .= "\nCONVERSATION HISTORY (Last 10 messages):\n";
        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
            $context .= "{$role}: {$msg->content}\n";
        }

        $systemPrompt = "You are a professional business assistant. Your task is to rewrite a rough draft or suggestion into a formal, professional, and friendly email reply.
        
        STRICT RULES:
        1. Use the provided context (notes and history) to make the email relevant and personal.
        2. Keep the tone professional but energetic and helpful.
        3. Do NOT include subject lines or placeholders like '[Your Name]'. Just the body of the email.
        4. If the suggestion is short, expand it into a complete, polite response.
        5. Ensure the email is formatted with clear paragraphs.";

        $userPrompt = "SUGGESTION TO REWRITE INTO PROFESSIONAL EMAIL:\n\"{$suggestion}\"";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt . "\n\n" . $context],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? '';
            }

            Log::error('AI Autowrite Failed', ['body' => $response->body()]);
            throw new \Exception('Failed to generate AI content.');
        } catch (\Exception $e) {
            Log::error('AI Autowrite Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
