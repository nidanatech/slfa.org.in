<?php

namespace App\Http\Controllers;

use App\Models\AiBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotPreviewController extends Controller
{
    public function sendMessage(Request $request, AiBot $bot)
    {
        $request->validate([
            'message' => 'required|string',
            'thread_id' => 'nullable|string',
        ]);

        $apiKey = $bot->tenant->openai_key ?? $bot->tenant->settings['openai_key'] ?? null;

        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API Key not configured.'], 400);
        }

        if (!$bot->assistant_id) {
            return response()->json(['error' => 'Bot has no Assistant ID. Please save the bot first.'], 400);
        }

        $threadId = $request->thread_id;
        $baseUrl = 'https://api.openai.com/v1';
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];

        // 1. Create Thread if not exists
        if (!$threadId) {
            $response = Http::withHeaders($headers)->post("$baseUrl/threads");
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to create thread: ' . $response->body()], 500);
            }
            $threadId = $response->json()['id'];
        }

        // 2. Add Message to Thread
        $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/messages", [
            'role' => 'user',
            'content' => $request->message,
        ]);
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to send message: ' . $response->body()], 500);
        }

        // 3. Run Assistant
        $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs", [
            'assistant_id' => $bot->assistant_id,
        ]);
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to start run: ' . $response->body()], 500);
        }
        $runId = $response->json()['id'];

        // 4. Poll for Completion
        $status = 'queued';
        $attempts = 0;
        $maxAttempts = 30; // 30 seconds max (prevent timeout)

        while (in_array($status, ['queued', 'in_progress', 'requires_action']) && $attempts < $maxAttempts) {
            sleep(1);
            $attempts++;

            $response = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/runs/$runId");
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to check run status'], 500);
            }
            
            $runData = $response->json();
            $status = $runData['status'];

            if ($status === 'requires_action') {
                // Handle Tool Calls (Mocking for Preview)
                $toolCalls = $runData['required_action']['submit_tool_outputs']['tool_calls'] ?? [];
                $toolOutputs = [];

                foreach ($toolCalls as $call) {
                    $functionName = $call['function']['name'];
                    $args = json_decode($call['function']['arguments'], true);
                    
                    Log::info("Preview Tool Call: $functionName", $args ?? []);

                    $output = "Tool '$functionName' executed successfully (Simulation).";
                    
                    // Specific mock responses for better UX
                    if ($functionName === 'book_appointment') {
                        $output = json_encode(['status' => 'success', 'message' => 'Appointment simulated for ' . ($args['preferred_time'] ?? 'requested time')]);
                    } elseif (str_starts_with($functionName, 'book_appointment_')) {
                        $output = json_encode(['status' => 'success', 'message' => "Appointment simulated via $functionName for " . ($args['preferred_time'] ?? 'requested time')]);
                    } elseif ($functionName === 'update_contact') {
                        $output = json_encode(['status' => 'success', 'message' => 'Contact field updated (Simulation).']);
                    }
                     elseif (str_starts_with($functionName, 'human_handover')) {
                        $output = json_encode(['status' => 'success', 'message' => 'Handover requested (Simulation).']);
                    }
                    elseif (str_starts_with($functionName, 'schedule_followup')) {
                        $output = json_encode(['status' => 'success', 'message' => 'Follow-up scheduled (Simulation).']);
                    }

                    $toolOutputs[] = [
                        'tool_call_id' => $call['id'],
                        'output' => $output,
                    ];
                }

                // Submit Outputs
                $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs/$runId/submit_tool_outputs", [
                    'tool_outputs' => $toolOutputs,
                ]);
                
                if (!$response->successful()) {
                   return response()->json(['error' => 'Failed to submit tool outputs: ' . $response->body()], 500);
                }
                
                // Continue polling
                $status = 'in_progress'; // Reset status to loop again
            }
        }

        if ($status !== 'completed') {
             return response()->json(['error' => "Run timed out or failed. Status: $status"], 500);
        }

        // 5. Get Messages & Usage
        // Fetch the run object again to get final usage stats
        $runResponse = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/runs/$runId");
        $finalRunData = $runResponse->json();
        $usage = $finalRunData['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

        // Log Token Usage
        if ($bot->tenant_id) {
             \App\Models\LlmTokenUsage::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'bot_session_id' => null, // Preview
                'conversation_id' => null, // Preview
                'contact_id' => null, // Preview
                'provider' => 'openai',
                'model' => $bot->model,
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens'],
                'request_type' => 'chat_preview',
                'purpose' => 'preview_reply',
                'latency_ms' => null, // Could track if needed
            ]);
        }

        $response = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/messages?limit=1");
        $messages = $response->json()['data'] ?? [];
        $lastMessage = $messages[0] ?? null;

        $content = '';
        if ($lastMessage && $lastMessage['role'] === 'assistant') {
            foreach ($lastMessage['content'] as $block) {
                if ($block['type'] === 'text') {
                    $content .= $block['text']['value'];
                }
            }
        }

        return response()->json([
            'response' => $content,
            'thread_id' => $threadId,
            'usage' => $usage // Optional: return usage to frontend
        ]);
    }
}
