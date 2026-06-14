<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebChatController extends Controller
{
    public function show($channelId)
    {
        // Use hashed ID lookup
        $channel = Channel::findByPublicIdOrFail($channelId);
        
        return Inertia::render('Widget/Index', [
            'channelId' => $channelId, // Pass the hash back
            'config' => $channel->config ?? []
        ]);
    }

    public function embed($channelId)
    {
        $channel = Channel::findByPublicId($channelId);

        if (!$channel) {
            return response('console.error("Sydkic: Channel not found");', 404)->header('Content-Type', 'application/javascript');
        }
        
        // Return raw JS with correct headers
        return response()->view('chat.embed', [
            'channelId' => $channelId,
            'url' => route('widget.show', $channelId)
        ])->header('Content-Type', 'application/javascript');
    }

    public function sendMessage(Request $request, $channelId)
    {
        $request->validate([
            'visitor_id' => 'required|string',
            'content' => 'required|string',
        ]);

        $channel = Channel::findByPublicIdOrFail($channelId);

        try {
            // 1. Find or Create Contact for this Visitor
            $contact = \App\Models\Contact::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_id' => $request->visitor_id,
                ],
                [
                    'tenant_id' => $channel->tenant_id, // Potential failure if null
                    'name' => 'Guest Visitor',
                    'avatar_url' => null, 
                ]
            );

            // 2. Find or Create Open Conversation
            $conversation = \App\Models\Conversation::where('channel_id', $channel->id)
                ->where('contact_id', $contact->id)
                ->where('status', '!=', 'closed')
                ->first();

            if (!$conversation) {
                $conversation = \App\Models\Conversation::create([
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'contact_id' => $contact->id,
                    'status' => 'open',
                ]);
            }

            // 3. Create Message
            $message = \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound', 
                'type' => 'text',
                'content' => $request->content,
                'sender_type' => 'contact', // Explicitly set sender_type for widget messages
                'status' => 'read',
            ]);
            
            $conversation->touch('last_message_at');

            // 4. Trigger Bot Response
            try {
                \Log::info("WebChat Trigger: Channel ID {$channel->id}, Tenant {$channel->tenant_id}");
                $botService = new \App\Services\BotService();
                $botService->triggerResponse($message);
            } catch (\Exception $e) {
                \Log::error("Bot Trigger Error: " . $e->getMessage());
            }

            return response()->json([
                'message' => $message,
                'conversation_id' => $conversation->id
            ]);
        } catch (\Exception $e) {
            \Log::error('WebChat Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function updateContact(Request $request, $channelId)
    {
        $request->validate([
            'visitor_id' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $channel = Channel::findByPublicIdOrFail($channelId);

        // Find the contact by visitor ID
        $contact = \App\Models\Contact::where('channel_id', $channel->id)
            ->where('external_id', $request->visitor_id)
            ->first();

        if ($contact) {
            $updateData = [];

            // Split name if provided
            if ($request->has('name')) {
                $nameParts = explode(' ', $request->name, 2);
                $updateData['first_name'] = $nameParts[0];
                $updateData['last_name'] = $nameParts[1] ?? '';
                // 'name' will be auto-updated by model event
            }

            // Map single email/phone to array structure if needed
            if ($request->has('email')) {
                // Check if email already exists in array, if not add it
                $existingEmails = $contact->emails ?? [];
                $emailExists = false;
                foreach ($existingEmails as $emailObj) {
                    if ($emailObj['value'] === $request->email) {
                        $emailExists = true;
                        break;
                    }
                }
                
                if (!$emailExists) {
                    $existingEmails[] = ['type' => 'personal', 'value' => $request->email];
                    $updateData['emails'] = $existingEmails;
                }
            }
            
             // Map single phone to array structure if needed
            if ($request->has('phone')) {
                $existingPhones = $contact->phones ?? [];
                $phoneExists = false;
                foreach ($existingPhones as $phoneObj) {
                    if ($phoneObj['value'] === $request->phone) {
                        $phoneExists = true;
                        break;
                    }
                }
                
                if (!$phoneExists) {
                    $existingPhones[] = ['type' => 'personal', 'value' => $request->phone];
                    $updateData['phones'] = $existingPhones;
                }
            }

            $contact->update($updateData);
        }

        return response()->json(['success' => true]);
    }
    public function getMessages(Request $request, $channelId)
    {
        try {
            $request->validate([
                'visitor_id' => 'required|string',
            ]);

            $channel = Channel::findByPublicIdOrFail($channelId);

            // Find contact
            $contact = \App\Models\Contact::where('channel_id', $channel->id)
                ->where('external_id', $request->visitor_id)
                ->first();

            if (!$contact) {
                return response()->json([]);
            }

            // active conversation
            $conversation = \App\Models\Conversation::where('channel_id', $channel->id)
                ->where('contact_id', $contact->id)
                ->where('status', '!=', 'closed')
                ->first();

            if (!$conversation) {
                return response()->json([]);
            }

            // Return messages
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) {
                    return [
                        'id' => $msg->id,
                        'text' => $msg->content,
                        'sender' => $msg->direction === 'inbound' ? 'user' : 'bot',
                        'status' => $msg->status,
                        'is_edited' => $msg->is_edited,
                        'time' => $msg->created_at ? $msg->created_at->toIso8601String() : now()->toIso8601String(),
                    ];
                });

            return response()->json($messages);
        } catch (\Exception $e) {
            \Log::error('WebChat getMessages Error: ' . $e->getMessage());
        }
    }

    public function updateMessageStatus(Request $request, $channelPublicId, \App\Models\Message $message)
    {
        $request->validate([
            'status' => 'required|in:delivered,read',
        ]);

        $channel = Channel::findByPublicIdOrFail($channelPublicId);
        
        if ($message->conversation->channel_id !== $channel->id) {
            abort(403);
        }

        $updateData = ['status' => $request->status];
        if ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
        } elseif ($request->status === 'read') {
            $updateData['seen_at'] = now();
        }

        $message->update($updateData);

        return response()->json(['success' => true]);
    }

    public function editMessage(Request $request, $channelPublicId, \App\Models\Message $message)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $channel = Channel::findByPublicIdOrFail($channelPublicId);
        
        if ($message->conversation->channel_id !== $channel->id) {
            abort(403);
        }

        $message->update([
            'content' => $request->content,
            'is_edited' => true,
            'edited_at' => now(),
            'status' => 'edited',
        ]);

        return response()->json(['success' => true]);
    }
}
