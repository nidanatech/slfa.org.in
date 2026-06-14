<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Services\ThreadsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ThreadsChannelDetailController extends Controller
{
    /**
     * Show channel detail page with posts
     */
    public function show(Channel $channel)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Channels/ThreadsDetail', [
            'channel' => $channel
        ]);
    }

    /**
     * Get posts for a channel (AJAX)
     */
    public function getPosts(Channel $channel, Request $request)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        try {
            $service = new ThreadsService($channel);
            $userId = $channel->config['user_id'];
            $limit = $request->query('limit', 25);
            $after = $request->query('after');

            $posts = $service->getUserThreads($userId, $limit, $after);

            if (!$posts) {
                return response()->json(['error' => 'Failed to fetch posts'], 500);
            }

            return response()->json($posts);

        } catch(\Exception $e) {
            Log::error('Failed to fetch Threads posts', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to fetch posts'], 500);
        }
    }

    /**
     * Create a new thread
     */
    public function createPost(Channel $channel, Request $request)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'text' => 'required|string|max:500',
            'media_type' => 'nullable|in:TEXT,IMAGE,VIDEO',
            'media_url' => 'nullable|url',
            'reply_control' => 'nullable|in:everyone,accounts_you_follow,mentioned_only'
        ]);

        try {
            $service = new ThreadsService($channel);
            
            $result = $service->createThread(
                $request->input('text'),
                $request->input('media_type', 'TEXT'),
                $request->input('media_url'),
                $request->input('reply_control', 'everyone')
            );

            if (!$result) {
                return response()->json(['error' => 'Failed to create thread'], 500);
            }

            return response()->json([
                'success' => true,
                'thread' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create thread', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to create thread'], 500);
        }
    }

    /**
     * Update a thread
     */
    public function updatePost(Channel $channel, $postId, Request $request)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'text' => 'required|string|max:500'
        ]);

        try {
            $service = new ThreadsService($channel);
            
            $result = $service->updateThread($postId, $request->input('text'));

            if (!$result) {
                return response()->json(['error' => 'Failed to update thread'], 500);
            }

            return response()->json([
                'success' => true,
                'thread' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update thread', [
                'channel_id' => $channel->id,
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to update thread'], 500);
        }
    }

    /**
     * Delete a thread
     */
    public function deletePost(Channel $channel, $postId)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        try {
            $service = new ThreadsService($channel);
            
            $result = $service->deleteThread($postId);

            if (!$result) {
                return response()->json(['error' => 'Failed to delete thread'], 500);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to delete thread', [
                'channel_id' => $channel->id,
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to delete thread'], 500);
        }
    }

    /**
     * Reply to a thread
     */
    public function replyToPost(Channel $channel, $postId, Request $request)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'text' => 'required|string|max:500',
            'media_type' => 'nullable|in:TEXT,IMAGE,VIDEO',
            'media_url' => 'nullable|url'
        ]);

        try {
            $service = new ThreadsService($channel);
            
            $result = $service->replyToThread(
                $postId,
                $request->input('text'),
                $request->input('media_type', 'TEXT'),
                $request->input('media_url')
            );

            if (!$result) {
                return response()->json(['error' => 'Failed to reply to thread'], 500);
            }

            return response()->json([
                'success' => true,
                'reply' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reply to thread', [
                'channel_id' => $channel->id,
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to reply to thread'], 500);
        }
    }

    /**
     * Get insights for a thread
     */
    public function getInsights(Channel $channel, $postId, Request $request)
    {
        if ($channel->user_id !== Auth::id() || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        try {
            $service = new ThreadsService($channel);
            
            $metrics = $request->query('metrics');
            if ($metrics) {
                $metrics = explode(',', $metrics);
            }

            $insights = $service->getInsights($postId, $metrics);

            if (!$insights) {
                return response()->json(['error' => 'Failed to fetch insights'], 500);
            }

            return response()->json($insights);

        } catch (\Exception $e) {
            Log::error('Failed to fetch thread insights', [
                'channel_id' => $channel->id,
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to fetch insights'], 500);
        }
    }
}
