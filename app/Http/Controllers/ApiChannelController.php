<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Str;

class ApiChannelController extends Controller
{
    public function ingest(Request $request, $token)
    {
        // 1. Find Channel
        $channel = Channel::where('type', 'api')
            ->where('config->api_token', $token)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Invalid Token'], 401);
        }

        // 2. Validate
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $tenantId = $channel->tenant_id;

        // 3. Find or Create Contact
        $contact = null;

        // Try by email
        if (!empty($data['email'])) {
            $contact = Contact::where('tenant_id', $tenantId)
                ->whereJsonContains('emails', [['value' => $data['email']]])
                ->orWhere('email', $data['email']) // Check legacy/direct field if exists
                ->first();
        }

        // Try by phone if not found
        if (!$contact && !empty($data['phone'])) {
            $contact = Contact::where('tenant_id', $tenantId)
                ->whereJsonContains('phones', [['value' => $data['phone']]])
                ->orWhere('phone', $data['phone'])
                ->first();
        }

        if (!$contact) {
            // Create Contact
            $contactData = [
                'tenant_id' => $tenantId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'source' => 'api_channel',
            ];

            if (!empty($data['email'])) {
                $contactData['emails'] = [['value' => $data['email'], 'type' => 'work']];
                $contactData['email'] = $data['email']; 
            }
            if (!empty($data['phone'])) {
                $contactData['phones'] = [['value' => $data['phone'], 'type' => 'mobile']];
                $contactData['phone'] = $data['phone'];
            }

            $contact = Contact::create($contactData);
        }


        // 4. Create Conversation
        // Check if there is an active conversation? For API messages, usually generic "Inbox".
        // Let's create a new conversation for simplicity or find open one.
        // For now, always create or find last open.
        
        $conversation = Conversation::where('contact_id', $contact->id)
            ->where('channel_id', $channel->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'channel_id' => $channel->id,
                'status' => 'open',
                'subject' => 'New Message from API',
            ]);
        }

        // 5. Create Message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => $data['message'],
            'direction' => 'inbound',
            'sender_type' => 'contact',
            'type' => 'text',
        ]);

        // Trigger Bot Response
        try {
            $botService = new \App\Services\BotService();
            $botService->triggerResponse($message);
        } catch (\Exception $e) {
            Log::error('ApiChannelController: Bot Trigger Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'contact_id' => $contact->id
        ], 201);
    }
}
