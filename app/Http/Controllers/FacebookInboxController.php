<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\FacebookService;
use App\Jobs\SyncFacebookMessages;
use App\Jobs\SyncFacebookComments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookInboxController extends Controller
{
    /**
     * Sync messages for a specific channel
     */
    public function syncMessages(Channel $channel)
    {
        if ($channel->type !== 'messenger') {
            return back()->with('error', 'Invalid channel type');
        }

        SyncFacebookMessages::dispatch($channel);

        return back()->with('success', 'Message sync started');
    }

    /**
     * Sync comments for a specific channel
     */
    public function syncComments(Channel $channel)
    {
        if ($channel->type !== 'messenger') {
            return back()->with('error', 'Invalid channel type');
        }

        SyncFacebookComments::dispatch($channel);

        return back()->with('success', 'Comment sync started');
    }

    /**
     * Reply to a Facebook message
     */
    public function replyToMessage(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        // Get the last inbound message to extract sender ID
        $lastMessage = $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNotNull('meta->message_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastMessage) {
            return response()->json(['error' => 'No messages found'], 404);
        }

        // Get sender ID from contact or message meta
        $recipientId = $conversation->contact->meta['facebook_id'] ?? null;
        
        if (!$recipientId) {
            // Try to extract from Facebook API
            $service = new FacebookService($conversation->channel);
            // For now, we'll need the sender ID from the webhook event
            // This is a limitation - we need to store it during sync
            return response()->json(['error' => 'Recipient ID not found'], 404);
        }

        try {
            $service = new FacebookService($conversation->channel);
            $result = $service->sendMessage($recipientId, $validated['content']);

            if (!$result) {
                return response()->json(['error' => 'Failed to send message'], 500);
            }

            // Create the reply message in the conversation
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $validated['content'],
                'direction' => 'outbound',
                'meta' => [
                    'message_id' => $result['message_id'] ?? null,
                    'type' => 'facebook_message',
                ],
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to send reply'], 500);
        }
    }

    /**
     * Reply to a Facebook comment (public)
     */
    public function replyToComment(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:8000',
            'comment_id' => 'required|string',
        ]);

        try {
            $service = new FacebookService($conversation->channel);
            $result = $service->replyToComment($validated['comment_id'], $validated['content']);

            if (!$result) {
                return response()->json(['error' => 'Failed to post reply'], 500);
            }

            // Create the reply message in the conversation
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $validated['content'],
                'direction' => 'outbound',
                'meta' => [
                    'comment_id' => $result['id'] ?? null,
                    'type' => 'facebook_comment',
                    'parent_comment_id' => $validated['comment_id'],
                ],
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Comment Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to post reply'], 500);
        }
    }

    /**
     * Send a private reply to a comment (DM)
     */
    public function privateReplyToComment(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'comment_id' => 'required|string',
        ]);

        try {
            $service = new FacebookService($conversation->channel);
            $result = $service->privateReplyToComment($validated['comment_id'], $validated['content']);

            if (!$result) {
                return response()->json(['error' => 'Failed to send private reply'], 500);
            }

            // Create the reply message in the conversation
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $validated['content'],
                'direction' => 'outbound',
                'meta' => [
                    'message_id' => $result['id'] ?? null,
                    'type' => 'facebook_private_reply',
                    'comment_id' => $validated['comment_id'],
                    'is_private_reply' => true,
                ],
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Private Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to send private reply'], 500);
        }
    }
}
