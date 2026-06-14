<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private const GRAPH_VERSION = 'v24.0';
    private const GRAPH_URL = 'https://graph.facebook.com/v24.0';

    protected Channel $channel;
    protected string $accessToken;
    protected string $instagramId;
    protected string $pageId;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->accessToken = $channel->config['access_token'] ?? '';
        $this->instagramId = $channel->config['instagram_id'] ?? '';
        $this->pageId = $channel->config['page_id'] ?? '';
    }

    /**
     * Get Instagram user profile
     */
    public function getUserProfile()
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$this->instagramId}", [
                'fields' => 'id,username,name,profile_picture_url,followers_count,follows_count,media_count',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getUserProfile failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram getUserProfile exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get media (posts) for the Instagram account
     */
    public function getMedia($limit = 25, $after = null)
    {
        try {
            $params = [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count,thumbnail_url',
                'limit' => $limit,
                'access_token' => $this->accessToken
            ];

            if ($after) {
                $params['after'] = $after;
            }

            $response = Http::get(self::GRAPH_URL . "/{$this->instagramId}/media", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getMedia failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram getMedia exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get comments on a media item
     */
    public function getMediaComments($mediaId, $limit = 50)
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$mediaId}/comments", [
                'fields' => 'id,text,username,timestamp,like_count,replies{id,text,username,timestamp}',
                'limit' => $limit,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getMediaComments failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram getMediaComments exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Reply to a comment
     */
    public function replyToComment($commentId, $text)
    {
        try {
            $response = Http::post(self::GRAPH_URL . "/{$commentId}/replies", [
                'message' => $text,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram replyToComment failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram replyToComment exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Hide a comment
     */
    public function hideComment($commentId)
    {
        try {
            $response = Http::post(self::GRAPH_URL . "/{$commentId}", [
                'hide' => true,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Instagram hideComment failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Instagram hideComment exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unhide a comment
     */
    public function unhideComment($commentId)
    {
        try {
            $response = Http::post(self::GRAPH_URL . "/{$commentId}", [
                'hide' => false,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Instagram unhideComment failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Instagram unhideComment exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get DM conversations
     */
    public function getConversations($limit = 25)
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$this->pageId}/conversations", [
                'fields' => 'id,updated_time,message_count,participants',
                'platform' => 'instagram',
                'limit' => $limit,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getConversations failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram getConversations exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get messages in a conversation
     */
    public function getMessages($conversationId, $limit = 25)
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$conversationId}", [
                'fields' => 'messages{id,message,from,created_time,attachments}',
                'limit' => $limit,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getMessages failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram getMessages exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send a direct message
     */
    public function sendMessage($recipientId, $text)
    {
        if (empty($this->accessToken)) {
            Log::error('Instagram sendMessage failed: Access token is empty', [
                'channel_id' => $this->channel->id,
                'recipient_id' => $recipientId
            ]);
            return null;
        }

        try {
            $response = Http::post(self::GRAPH_URL . "/{$this->pageId}/messages", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $text],
                'messaging_type' => 'RESPONSE',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'channel_id' => $this->channel->id,
                'page_id' => $this->pageId,
                'instagram_id' => $this->instagramId,
                'token_length' => strlen($this->accessToken),
                'token_prefix' => substr($this->accessToken, 0, 10) . '...',
                'recipient_id' => $recipientId
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram sendMessage exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getUserInfo($igUserId)
    {
        try {
            // Note: For Instagram Messaging (scoped IDs), the field is 'profile_pic'
            // For Instagram Business API (mentions/comments on the IG account itself), it's 'profile_picture_url'
            $response = Http::get(self::GRAPH_URL . "/{$igUserId}", [
                'fields' => 'id,name,username,profile_pic',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Map profile_pic to profile_picture_url for consistency in our app
                if (isset($data['profile_pic'])) {
                    $data['profile_picture_url'] = $data['profile_pic'];
                }
                return $data;
            }

            // Fallback for different ID types if needed
            Log::warning('Instagram getUserInfo failed with profile_pic, trying profile_picture_url', [
                'status' => $response->status(),
                'ig_user_id' => $igUserId
            ]);

            $response = Http::get(self::GRAPH_URL . "/{$igUserId}", [
                'fields' => 'id,name,username,profile_picture_url',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Instagram getUserInfo totally failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'ig_user_id' => $igUserId,
                'token_prefix' => substr($this->accessToken, 0, 10) . '...'
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Instagram getUserInfo exception', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId
            ]);
            return null;
        }
    }
}
