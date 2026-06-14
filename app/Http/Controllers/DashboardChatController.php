<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\DashboardConversation;
use App\Models\DashboardMessage;
use App\Services\DashboardAssistantService;

class DashboardChatController extends Controller
{
    public function history()
    {
        $conversations = DashboardConversation::where('user_id', Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'last_message_at' => $conv->last_message_at ? $conv->last_message_at->diffForHumans() : 'Just now',
                    'preview' => $conv->messages()->orderBy('created_at', 'desc')->first()->content ?? 'New Chat'
                ];
            });

        return response()->json(['conversations' => $conversations]);
    }

    public function show($id)
    {
        $conversation = DashboardConversation::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['messages' => function($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->firstOrFail();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $conversation->messages
        ]);
    }

    public function chat(Request $request, DashboardAssistantService $service)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|exists:dashboard_conversations,id'
        ]);

        $user = Auth::user();
        $tenant = $user->tenant;
        $messageContent = $request->input('message');
        $conversationId = $request->input('conversation_id');

        // 1. Ensure Assistant
        if (!$tenant) {
            return response()->json(['error' => 'No active tenant found.'], 403);
        }

        try {
            $assistantId = $service->ensureAssistant($tenant);
        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Configuration Error: ' . $e->getMessage()], 500);
        }

        // 2. Find or Create Conversation (Reuse existing thread if within 24 hours)
        $conversation = null;
        if ($conversationId) {
            $conversation = DashboardConversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Find an active conversation from the last 24 hours for this user
            $conversation = DashboardConversation::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $headers = $service->getHeaders();
        $baseUrl = 'https://api.openai.com/v1';

        if (!$conversation) {
            // Create Thread
            $threadResponse = Http::withHeaders($headers)->post("$baseUrl/threads");
            if (!$threadResponse->successful()) {
                Log::error("Failed to create OpenAI thread", ['body' => $threadResponse->body()]);
                return response()->json(['error' => 'Failed to create AI session'], 500);
            }
            $threadId = $threadResponse->json()['id'];

            $conversation = DashboardConversation::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id ?? null,
                'title' => substr($messageContent, 0, 50) . '...',
                'thread_id' => $threadId,
                'last_message_at' => now(),
            ]);
        }
        
        $threadId = $conversation->thread_id;

        // 3. Save User Message
        DashboardMessage::create([
            'dashboard_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageContent,
            'run_id' => null
        ]);

        // 4. Send Message to OpenAI
        $response = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/messages", [
            'role' => 'user',
            'content' => $messageContent,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to send message to AI'], 500);
        }

        // 5. Run Assistant
        $runResponse = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs", [
            'assistant_id' => $assistantId,
        ]);

        if (!$runResponse->successful()) {
             return response()->json(['error' => 'Failed to start AI run'], 500);
        }

        $runId = $runResponse->json()['id'];

        // 6. Polling & Tool Loop
        $status = 'queued';
        $attempts = 0;
        $maxAttempts = 120; // Increased further for complex tool chains

        while (in_array($status, ['queued', 'in_progress', 'requires_action']) && $attempts < $maxAttempts) {
            sleep(1);
            $attempts++;
            
            // Check Status
            $checkResponse = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/runs/$runId");
            if (!$checkResponse->successful()) {
                 $status = 'failed'; 
                 break;
            }
            
            $runData = $checkResponse->json();
            $status = $runData['status'];

            if ($status === 'requires_action') {
                // Handle Tools
                $toolCalls = $runData['required_action']['submit_tool_outputs']['tool_calls'] ?? [];
                $toolOutputs = [];
                
                foreach ($toolCalls as $call) {
                    $functionName = $call['function']['name'];
                    $arguments = json_decode($call['function']['arguments'], true) ?? [];
                    
                    // Run Tool via Service
                    try {
                        $output = $service->runTool($user, $functionName, $arguments);
                    } catch (\Exception $e) {
                        $output = json_encode(['error' => $e->getMessage()]);
                    }
                    
                    $toolOutputs[] = [
                        'tool_call_id' => $call['id'],
                        'output' => (string) $output
                    ];
                }

                // Submit Outputs
                $submitResponse = Http::withHeaders($headers)->post("$baseUrl/threads/$threadId/runs/$runId/submit_tool_outputs", [
                    'tool_outputs' => $toolOutputs
                ]);
                
                if (!$submitResponse->successful()) {
                    Log::error("Failed to submit tool outputs", ['body' => $submitResponse->body()]);
                    $status = 'failed';
                    break;
                }
            }
        }

        if ($status === 'completed') {
            $msgResponse = Http::withHeaders($headers)->get("$baseUrl/threads/$threadId/messages?limit=1");
            $reply = "I didn't get a response.";
            $messages = $msgResponse->json()['data'] ?? [];
            
            if (!empty($messages)) {
                $lastMsg = $messages[0];
                if ($lastMsg['role'] === 'assistant') {
                    $content = '';
                    foreach ($lastMsg['content'] as $block) {
                        if ($block['type'] === 'text') {
                            $content .= $block['text']['value'];
                        }
                    }
                    $reply = $content;
                }
            }

            // Save Assistant Message
            DashboardMessage::create([
                'dashboard_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $reply,
                'run_id' => $runId,
            ]);

            $conversation->update(['last_message_at' => now()]);

            // 7. Log Activity
            if ($tenant) {
                \App\Models\Activity::create([
                    'tenant_id' => $tenant->id,
                    'contact_id' => null,
                    'activity_type' => 'ai_assistant_chat',
                    'source_table' => 'dashboard_conversations',
                    'source_id' => $conversation->id,
                    'summary' => "AI Assistant replied: " . substr(strip_tags($reply), 0, 100) . "...",
                    'actor_id' => $user->id,
                ]);
            }

            return response()->json([
                'reply' => $reply,
                'conversation_id' => $conversation->id,
                'title' => $conversation->title
            ]);

        } else {
            return response()->json(['error' => "AI Request timed out or failed (Status: $status)"], 500);
        }
    }
}
