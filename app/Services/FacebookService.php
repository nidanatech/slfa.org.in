<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookService
{
    private const GRAPH_VERSION = 'v24.0';
    private const GRAPH_URL = 'https://graph.facebook.com/v24.0';

    protected Channel $channel;
    protected string $pageAccessToken;
    protected string $pageId;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->pageAccessToken = $channel->config['access_token'] ?? '';
        $this->pageId = $channel->config['page_id'] ?? '';
    }

    /**
     * Get all conversations for the page
     */
    public function getConversations(int $limit = 25): array
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$this->pageId}/conversations", [
                'access_token' => $this->pageAccessToken,
                'fields' => 'id,updated_time,participants,messages{id,from,to,created_time,message,attachments}',
                'limit' => $limit,
            ]);

            if ($response->failed()) {
                Log::error('Facebook Get Conversations Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['data'] ?? [];

        } catch (Exception $e) {
            Log::error('Facebook Get Conversations Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get messages for a specific conversation
     */
    public function getConversationMessages(string $conversationId, int $limit = 50): array
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$conversationId}/messages", [
                'access_token' => $this->pageAccessToken,
                'fields' => 'id,from,to,created_time,message,attachments,sticker,shares',
                'limit' => $limit,
            ]);

            if ($response->failed()) {
                Log::error('Facebook Get Messages Failed', [
                    'conversation_id' => $conversationId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['data'] ?? [];

        } catch (Exception $e) {
            Log::error('Facebook Get Messages Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Send a message reply
     */
    public function sendMessage(string $recipientId, string $message, array $attachments = []): ?array
    {
        try {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message],
                'messaging_type' => 'RESPONSE',
            ];

            // Add attachments if provided
            if (!empty($attachments)) {
                $payload['message']['attachment'] = $attachments[0]; // Facebook supports one attachment per message
            }

            $response = Http::post(self::GRAPH_URL . "/me/messages", array_merge($payload, [
                'access_token' => $this->pageAccessToken,
            ]));

            if ($response->failed()) {
                Log::error('Facebook Send Message Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Facebook Send Message Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get comments for a post
     */
    public function getPostComments(string $postId, int $limit = 50): array
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$postId}/comments", [
                'access_token' => $this->pageAccessToken,
                'fields' => 'id,from,message,created_time,attachment,comment_count,like_count,parent,permalink_url',
                'limit' => $limit,
                'filter' => 'stream', // Get all comments including replies
            ]);

            if ($response->failed()) {
                Log::error('Facebook Get Comments Failed', [
                    'post_id' => $postId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['data'] ?? [];

        } catch (Exception $e) {
            Log::error('Facebook Get Comments Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Reply to a comment (public reply)
     */
    public function replyToComment(string $commentId, string $message): ?array
    {
        try {
            $response = Http::post(self::GRAPH_URL . "/{$commentId}/comments", [
                'access_token' => $this->pageAccessToken,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('Facebook Reply to Comment Failed', [
                    'comment_id' => $commentId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Facebook Reply to Comment Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Send a private reply to a comment (DM)
     */
    public function privateReplyToComment(string $commentId, string $message): ?array
    {
        try {
            $response = Http::post(self::GRAPH_URL . "/{$commentId}/private_replies", [
                'access_token' => $this->pageAccessToken,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('Facebook Private Reply Failed', [
                    'comment_id' => $commentId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Facebook Private Reply Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get page posts
     */
    public function getPagePosts(int $limit = 25): array
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$this->pageId}/posts", [
                'access_token' => $this->pageAccessToken,
                'fields' => 'id,message,created_time,permalink_url,comments.summary(true)',
                'limit' => $limit,
            ]);

            if ($response->failed()) {
                Log::error('Facebook Get Posts Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['data'] ?? [];

        } catch (Exception $e) {
            Log::error('Facebook Get Posts Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get user profile information
     * Handles both Facebook PSIDs and Instagram-Scoped IDs (IGSIDs)
     */
    public function getUserProfile(string $userId): ?array
    {
        try {
            // Standard Profile Request
            $fields = 'id,first_name,last_name,name,profile_pic,locale,timezone,gender';
            $response = Http::get(self::GRAPH_URL . "/{$userId}", [
                'access_token' => $this->pageAccessToken,
                'fields' => $fields,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Facebook Get User Profile Failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;

        } catch (Exception $e) {
            Log::error('Facebook Get User Profile Exception', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user profile picture URL using the /picture edge
     */
    public function getUserPictureUrl(string $userId, string $type = 'large'): ?string
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/{$userId}/picture", [
                'access_token' => $this->pageAccessToken,
                'redirect' => 'false',
                'type' => $type,
            ]);

            if ($response->successful()) {
                return $response->json()['data']['url'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Validate page access token
     */
    public function validateToken(): bool
    {
        try {
            $response = Http::get(self::GRAPH_URL . "/me", [
                'access_token' => $this->pageAccessToken,
            ]);

            return $response->successful();

        } catch (Exception $e) {
            Log::error('Facebook Token Validation Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
