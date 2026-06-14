<?php

namespace App\Http\Controllers;

use App\Models\TeamChannel;
use App\Models\TeamMessage;
use Illuminate\Http\Request;

class TeamMessageController extends Controller
{
    /**
     * Get messages for a channel (paginated)
     */
    public function index(Request $request, $channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        // Verify user has access to channel
        $channel = TeamChannel::forTenant($tenantId)
            ->forUser($userId)
            ->findOrFail($channelId);

        $since = $request->get('since'); // For polling

        $query = TeamMessage::inChannel($channelId)
            ->topLevel()
            ->with(['user', 'replies.user']);

        if ($since) {
            $query->where('id', '>', $since);
        }

        $messages = $query->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message to a channel
     */
    public function store(Request $request, $channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        // Verify user has access to channel
        $channel = TeamChannel::forTenant($tenantId)
            ->forUser($userId)
            ->findOrFail($channelId);

        $validated = $request->validate([
            'content' => 'required|string|min:1',
            'parent_id' => 'nullable|exists:team_messages,id',
        ]);

        $message = TeamMessage::create([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'message_type' => 'text',
        ]);

        // Update channel's last_message_at
        $channel->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    /**
     * Edit a message
     */
    public function update(Request $request, $messageId)
    {
        $userId = auth()->id();

        $message = TeamMessage::findOrFail($messageId);

        // Check ownership
        if (!$message->isOwnedBy($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|min:1',
        ]);

        $message->update(['content' => $validated['content']]);
        $message->markAsEdited();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Delete a message
     */
    public function destroy($messageId)
    {
        $userId = auth()->id();

        $message = TeamMessage::findOrFail($messageId);

        // Check ownership
        if (!$message->isOwnedBy($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Mark channel as read
     */
    public function markAsRead($channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $channel = TeamChannel::forTenant($tenantId)
            ->forUser($userId)
            ->findOrFail($channelId);

        $channel->markAsRead($userId);

        return response()->json([
            'success' => true,
        ]);
    }
}
