<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GoogleBusinessService;
use App\Jobs\SyncGoogleBusinessReviews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleBusinessController extends Controller
{
    /**
     * Sync reviews for a specific channel
     */
    public function syncReviews(Channel $channel)
    {
        if ($channel->type !== 'google_business') {
            return back()->with('error', 'Invalid channel type');
        }

        SyncGoogleBusinessReviews::dispatch($channel);

        return back()->with('success', 'Review sync started');
    }

    /**
     * Reply to a review (called from inbox)
     */
    public function replyToReview(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:4096',
        ]);

        // Get the review message to extract review_name
        $reviewMessage = $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNotNull('meta->review_name')
            ->first();

        if (!$reviewMessage) {
            return response()->json(['error' => 'Review not found'], 404);
        }

        $reviewName = $reviewMessage->meta['review_name'] ?? null;
        if (!$reviewName) {
            return response()->json(['error' => 'Review name not found'], 404);
        }

        try {
            $service = new GoogleBusinessService($conversation->channel);
            $result = $service->replyToReview($reviewName, $validated['content']);

            if (!$result) {
                return response()->json(['error' => 'Failed to post reply'], 500);
            }

            // Create the reply message in the conversation
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $validated['content'],
                'direction' => 'outbound',
                'meta' => [
                    'review_reply' => true,
                    'review_name' => $reviewName,
                    'type' => 'google_business_review_reply',
                ],
            ]);

            // Update conversation
            $conversation->update([
                'status' => 'closed',
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('GMB Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to post reply'], 500);
        }
    }

    /**
     * Update an existing review reply
     */
    public function updateReviewReply(Request $request, Conversation $conversation, Message $message)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:4096',
        ]);

        if ($message->conversation_id !== $conversation->id) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        $reviewName = $message->meta['review_name'] ?? null;
        if (!$reviewName) {
            return response()->json(['error' => 'Review name not found'], 404);
        }

        try {
            $service = new GoogleBusinessService($conversation->channel);
            $result = $service->updateReviewReply($reviewName, $validated['content']);

            if (!$result) {
                return response()->json(['error' => 'Failed to update reply'], 500);
            }

            // Update the message
            $message->update([
                'content' => $validated['content'],
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('GMB Update Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update reply'], 500);
        }
    }

    /**
     * Delete a review reply
     */
    public function deleteReviewReply(Conversation $conversation, Message $message)
    {
        if ($message->conversation_id !== $conversation->id) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        $reviewName = $message->meta['review_name'] ?? null;
        if (!$reviewName) {
            return response()->json(['error' => 'Review name not found'], 404);
        }

        try {
            $service = new GoogleBusinessService($conversation->channel);
            $success = $service->deleteReviewReply($reviewName);

            if (!$success) {
                return response()->json(['error' => 'Failed to delete reply'], 500);
            }

            // Delete the message
            $message->delete();

            // Update conversation status
            $conversation->update([
                'status' => 'open',
            ]);

            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('GMB Delete Reply Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete reply'], 500);
        }
    }
}
