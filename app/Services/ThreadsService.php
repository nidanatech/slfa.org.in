<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreadsService
{
    private const GRAPH_VERSION = 'v1.0';
    private const GRAPH_URL = 'https://graph.threads.net/v1.0';

    protected Channel $channel;
    protected string $accessToken;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->accessToken = $channel->config['access_token'] ?? '';
    }

    /**
     * Get authenticated user's profile
     */
    public function getUserProfile()
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . '/me', [
                    'fields' => 'id,username,name,threads_profile_picture_url,threads_biography'
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getUserProfile failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getUserProfile exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user's threads/posts with pagination
     */
    public function getUserThreads($userId, $limit = 25, $after = null)
    {
        try {
            $params = [
                'fields' => 'id,media_product_type,media_type,media_url,permalink,text,timestamp,username,is_quote_post',
                'limit' => $limit
            ];

            if ($after) {
                $params['after'] = $after;
            }

            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . "/{$userId}/threads", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getUserThreads failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getUserThreads exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get single thread details
     */
    public function getThread($threadId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . "/{$threadId}", [
                    'fields' => 'id,media_product_type,media_type,media_url,permalink,text,timestamp,username,is_quote_post,owner,has_replies,root_post,replied_to,hide_status,reply_audience'
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getThread failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getThread exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get replies to a specific thread
     */
    public function getReplies($threadId, $limit = 25, $after = null)
    {
        try {
            $params = [
                'fields' => 'id,text,username,permalink,timestamp,media_product_type,media_type,media_url,has_replies,is_reply,is_reply_owned_by_me,hide_status',
                'limit' => $limit
            ];

            if ($after) {
                $params['after'] = $after;
            }

            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . "/{$threadId}/replies", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getReplies failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getReplies exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get conversation (thread with all replies)
     */
    public function getConversation($threadId)
    {
        try {
            $params = [
                'fields' => 'id,text,username,permalink,timestamp,media_product_type,media_type,media_url,has_replies,is_reply,is_reply_owned_by_me,hide_status,children{id,text,username,timestamp}'
            ];

            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . "/{$threadId}/conversation", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getConversation failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getConversation exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create a new thread
     */
    public function createThread($text, $mediaType = 'TEXT', $mediaUrl = null, $replyControl = 'everyone')
    {
        try {
            $userId = $this->channel->config['user_id'];

            $params = [
                'media_type' => $mediaType,
                'text' => $text,
                'reply_control' => $replyControl
            ];

            if ($mediaUrl && in_array($mediaType, ['IMAGE', 'VIDEO'])) {
                $params[$mediaType === 'IMAGE' ? 'image_url' : 'video_url'] = $mediaUrl;
            }

            // First, create the media container
            $response = Http::withToken($this->accessToken)
                ->post(self::GRAPH_URL . "/{$userId}/threads", $params);

            if ($response->successful()) {
                $data = $response->json();
                $creationId = $data['id'];

                // Then, publish it
                $publishResponse = Http::withToken($this->accessToken)
                    ->post(self::GRAPH_URL . "/{$userId}/threads_publish", [
                        'creation_id' => $creationId
                    ]);

                if ($publishResponse->successful()) {
                    return $publishResponse->json();
                }

                Log::error('Threads publish failed', [
                    'status' => $publishResponse->status(),
                    'body' => $publishResponse->body()
                ]);
                return null;
            }

            Log::error('Threads createThread failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads createThread exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update/Edit a thread (text only)
     */
    public function updateThread($threadId, $text)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post(self::GRAPH_URL . "/{$threadId}", [
                    'text' => $text
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads updateThread failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads updateThread exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete a thread
     */
    public function deleteThread($threadId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->delete(self::GRAPH_URL . "/{$threadId}");

            if ($response->successful()) {
                return true;
            }

            Log::error('Threads deleteThread failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Threads deleteThread exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reply to a thread
     */
    public function replyToThread($threadId, $text, $mediaType = 'TEXT', $mediaUrl = null)
    {
        try {
            $userId = $this->channel->config['user_id'];

            $params = [
                'media_type' => $mediaType,
                'text' => $text,
                'reply_to_id' => $threadId
            ];

            if ($mediaUrl && in_array($mediaType, ['IMAGE', 'VIDEO'])) {
                $params[$mediaType === 'IMAGE' ? 'image_url' : 'video_url'] = $mediaUrl;
            }

            // Create the reply container
            $response = Http::withToken($this->accessToken)
                ->post(self::GRAPH_URL . "/{$userId}/threads", $params);

            if ($response->successful()) {
                $data = $response->json();
                $creationId = $data['id'];

                // Publish the reply
                $publishResponse = Http::withToken($this->accessToken)
                    ->post(self::GRAPH_URL . "/{$userId}/threads_publish", [
                        'creation_id' => $creationId
                    ]);

                if ($publishResponse->successful()) {
                    return $publishResponse->json();
                }

                Log::error('Threads reply publish failed', [
                    'status' => $publishResponse->status(),
                    'body' => $publishResponse->body()
                ]);
                return null;
            }

            Log::error('Threads replyToThread failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads replyToThread exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Hide a reply (moderation)
     */
    public function hideReply($replyId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post(self::GRAPH_URL . "/{$replyId}/manage_reply", [
                    'hide' => true
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Threads hideReply failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Threads hideReply exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unhide a reply (moderation)
     */
    public function unhideReply($replyId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post(self::GRAPH_URL . "/{$replyId}/manage_reply", [
                    'hide' => false
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Threads unhideReply failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Threads unhideReply exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get insights/analytics for a thread
     */
    public function getInsights($threadId, $metrics = null)
    {
        try {
            if (!$metrics) {
                $metrics = ['views', 'likes', 'replies', 'reposts', 'quotes'];
            }

            $response = Http::withToken($this->accessToken)
                ->get(self::GRAPH_URL . "/{$threadId}/insights", [
                    'metric' => implode(',', $metrics)
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Threads getInsights failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Threads getInsights exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
