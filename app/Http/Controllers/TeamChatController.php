<?php

namespace App\Http\Controllers;

use App\Models\TeamChannel;
use App\Models\TeamMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamChatController extends Controller
{
    /**
     * List all channels for the authenticated user
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        // Get user's channels with unread counts
        $channels = TeamChannel::forTenant($tenantId)
            ->forUser($userId)
            ->active()
            ->with(['latestMessage.user', 'participants'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function($channel) use ($userId) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'slug' => $channel->slug,
                    'type' => $channel->type,
                    'description' => $channel->description,
                    'last_message_at' => $channel->last_message_at,
                    'latest_message' => $channel->latestMessage,
                    'unread_count' => $channel->getUnreadCount($userId),
                    'participant_count' => $channel->participants->count(),
                ];
            });

        return response()->json([
            'channels' => $channels,
        ]);
    }

    /**
     * Create a new channel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:public,private,group',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Create channel
        $channel = TeamChannel::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'created_by' => auth()->id(),
        ]);

        // Add creator as owner
        $channel->addParticipant(auth()->id(), 'owner');

        // Add other members
        if (!empty($validated['member_ids'])) {
            foreach ($validated['member_ids'] as $userId) {
                if ($userId != auth()->id()) {
                    $channel->addParticipant($userId, 'member');
                }
            }
        }

        // For public channels, add all tenant users
        if ($validated['type'] === 'public') {
            $tenantUsers = \App\Models\User::where('tenant_id', $tenantId)
                ->where('id', '!=', auth()->id())
                ->pluck('id');
            
            foreach ($tenantUsers as $userId) {
                if (!$channel->isParticipant($userId)) {
                    $channel->addParticipant($userId, 'member');
                }
            }
        }

        return response()->json([
            'success' => true,
            'channel' => $channel->load('participants'),
        ]);
    }

    /**
     * Get channel details with messages
     */
    public function show($channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $channel = TeamChannel::forTenant($tenantId)
            ->forUser($userId)
            ->with(['participants.user', 'creator'])
            ->findOrFail($channelId);

        // Get messages
        $messages = TeamMessage::inChannel($channelId)
            ->topLevel()
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();

        // Mark as read
        $channel->markAsRead($userId);

        return response()->json([
            'channel' => $channel,
            'messages' => $messages,
        ]);
    }

    /**
     * Update channel settings
     */
    public function update(Request $request, $channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $channel = TeamChannel::forTenant($tenantId)
            ->findOrFail($channelId);

        // Check if user is admin
        $participant = $channel->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant || !$participant->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $channel->update($validated);

        return response()->json([
            'success' => true,
            'channel' => $channel,
        ]);
    }

    /**
     * Archive/delete channel
     */
    public function destroy($channelId)
    {
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $channel = TeamChannel::forTenant($tenantId)
            ->findOrFail($channelId);

        // Check if user is owner
        $participant = $channel->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant || !$participant->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channel->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Channel archived successfully',
        ]);
    }

    /**
     * Add member to channel
     */
    public function addMember(Request $request, $channelId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $channel = TeamChannel::forTenant($tenantId)->findOrFail($channelId);

        // Check if already a participant
        if ($channel->isParticipant($validated['user_id'])) {
            return response()->json(['error' => 'User is already a member'], 400);
        }

        $channel->addParticipant($validated['user_id'], 'member');

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully',
        ]);
    }

    /**
     * Remove member from channel
     */
    public function removeMember($channelId, $userId)
    {
        $tenantId = auth()->user()->tenant_id;
        $channel = TeamChannel::forTenant($tenantId)->findOrFail($channelId);

        $channel->removeParticipant($userId);

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully',
        ]);
    }
}
