<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeroChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'session_id' => 'required|string',
            'user_name' => 'nullable|string|max:100',
            'history' => 'nullable|array',
        ]);

        $message = $validated['message'];
        $userName = $validated['user_name'];
        $history = $validated['history'] ?? [];

        // System prompt for the Hero Sydkic
        $systemPrompt = "You are 'Sydkic AI', a friendly and professional business automation expert. 
        Your goal is to help visitors understand how Sydkic can automate their business activities (Marketing, Sales, Service).
        
        Guidelines:
        - If the user provides their name, acknowledge it warmly.
        - ALWAYS suggest booking a demo or exploring features if the conversation leads there.
        - If you sense they want to book/schedule, include the keyword 'BOOK_WIDGET' in your response.
        - If they ask what you can do/features, include the keyword 'FEATURE_WIDGET' in your response.
        - Keep responses concise and engaging.
        - Do not mention GPT or OpenAI.";

        try {
            $aiService = new AiService();
            
            // Build the messages array for GPT
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            // Limit history to last 5 exchanges to save tokens
            foreach (array_slice($history, -10) as $cachedMsg) {
                $messages[] = [
                    'role' => $cachedMsg['type'] === 'user' ? 'user' : 'assistant',
                    'content' => $cachedMsg['content'],
                ];
            }

            // Acknowledge name if it's the first time we see it in this message
            $prompt = $message;
            if ($userName && stripos($message, $userName) === false && count($history) < 3) {
                $prompt = "My name is {$userName}. {$message}";
            }

            $messages[] = ['role' => 'user', 'content' => $prompt];

            // Direct API call via Http to bypass model limitations if needed, 
            // but generateText works fine
            $response = $aiService->generateText($prompt, $systemPrompt, 500, 0.7);

            // Check for widget triggers
            $widget = null;
            if (stripos($response, 'BOOK_WIDGET') !== false) {
                $widget = 'booking';
                $response = str_ireplace('BOOK_WIDGET', '', $response);
            } elseif (stripos($response, 'FEATURE_WIDGET') !== false) {
                $widget = 'features';
                $response = str_ireplace('FEATURE_WIDGET', '', $response);
            }

            return response()->json([
                'success' => true,
                'content' => trim($response),
                'widget' => $widget,
            ]);

        } catch (\Exception $e) {
            Log::error('Hero Chatbot Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'content' => "I'm having a slight technical hiccup, but I'm still here! How else can I assist you?",
            ], 500);
        }
    }
}
