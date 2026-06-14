<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleBusinessService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const ACCOUNTS_API = 'https://mybusinessaccountmanagement.googleapis.com/v1';
    private const BUSINESS_INFO_API = 'https://mybusinessbusinessinformation.googleapis.com/v1';
    
    // Reviews API (v4 - still active for reviews)
    private const REVIEWS_API = 'https://mybusiness.googleapis.com/v4';
    
    // Posts API (Local Posts)
    private const POSTS_API = 'https://mybusiness.googleapis.com/v4';

    protected Channel $channel;
    protected string $accessToken;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->ensureValidToken();
    }

    /**
     * Ensure we have a valid access token, refresh if needed
     */
    protected function ensureValidToken(): void
    {
        $config = $this->channel->config;
        $expiresAt = $config['expires_at'] ?? null;

        // Check if token is expired or will expire in next 5 minutes
        if (!$expiresAt || now()->addMinutes(5)->isAfter($expiresAt)) {
            $this->refreshAccessToken();
        } else {
            $this->accessToken = $config['access_token'];
        }
    }

    /**
     * Refresh the access token using refresh token
     */
    protected function refreshAccessToken(): void
    {
        $config = $this->channel->config;
        $refreshToken = $config['refresh_token'] ?? null;

        if (!$refreshToken) {
            throw new Exception('No refresh token available for channel: ' . $this->channel->id);
        }

        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('GMB Token Refresh Failed', ['response' => $data]);
                throw new Exception('Failed to refresh access token');
            }

            // Update channel config with new token
            $config['access_token'] = $data['access_token'];
            $config['expires_at'] = now()->addSeconds($data['expires_in'] ?? 3600)->toDateTimeString();
            
            $this->channel->config = $config;
            $this->channel->save();

            $this->accessToken = $data['access_token'];

            Log::info('GMB Token Refreshed', ['channel_id' => $this->channel->id]);

        } catch (Exception $e) {
            Log::error('GMB Token Refresh Exception', [
                'channel_id' => $this->channel->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get location name from channel config
     */
    protected function getLocationName(): string
    {
        return $this->channel->config['location_name'] ?? '';
    }

    /**
     * Fetch all reviews for this location
     * 
     * @return array
     */
    public function getReviews(): array
    {
        $locationName = $this->getLocationName();
        
        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::REVIEWS_API . "/{$locationName}/reviews");

            if ($response->failed()) {
                Log::error('GMB Get Reviews Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['reviews'] ?? [];

        } catch (Exception $e) {
            Log::error('GMB Get Reviews Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Reply to a review
     * 
     * @param string $reviewName The review resource name (e.g., "accounts/123/locations/456/reviews/789")
     * @param string $comment The reply text
     * @return array|null
     */
    public function replyToReview(string $reviewName, string $comment): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->put(self::REVIEWS_API . "/{$reviewName}/reply", [
                    'comment' => $comment
                ]);

            if ($response->failed()) {
                Log::error('GMB Reply to Review Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('GMB Reply to Review Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update an existing review reply
     * 
     * @param string $reviewName
     * @param string $comment
     * @return array|null
     */
    public function updateReviewReply(string $reviewName, string $comment): ?array
    {
        // Same endpoint as reply - PUT will update if reply exists
        return $this->replyToReview($reviewName, $comment);
    }

    /**
     * Delete a review reply
     * 
     * @param string $reviewName
     * @return bool
     */
    public function deleteReviewReply(string $reviewName): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->delete(self::REVIEWS_API . "/{$reviewName}/reply");

            if ($response->failed()) {
                Log::error('GMB Delete Review Reply Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('GMB Delete Review Reply Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create a local post (with optional media)
     * 
     * @param array $postData
     * @return array|null
     */
    public function createPost(array $postData): ?array
    {
        $locationName = $this->getLocationName();

        try {
            $response = Http::withToken($this->accessToken)
                ->post(self::POSTS_API . "/{$locationName}/localPosts", $postData);

            if ($response->failed()) {
                Log::error('GMB Create Post Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('GMB Create Post Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Upload media (image or video) for use in posts
     * 
     * @param string $mediaUrl URL or base64 data
     * @param string $mediaFormat 'PHOTO' or 'VIDEO'
     * @return array|null
     */
    public function uploadMedia(string $mediaUrl, string $mediaFormat = 'PHOTO'): ?array
    {
        $locationName = $this->getLocationName();

        try {
            $response = Http::withToken($this->accessToken)
                ->post(self::POSTS_API . "/{$locationName}/media", [
                    'mediaFormat' => $mediaFormat,
                    'sourceUrl' => $mediaUrl,
                ]);

            if ($response->failed()) {
                Log::error('GMB Upload Media Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('GMB Upload Media Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all local posts
     * 
     * @return array
     */
    public function getPosts(): array
    {
        $locationName = $this->getLocationName();

        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::POSTS_API . "/{$locationName}/localPosts");

            if ($response->failed()) {
                Log::error('GMB Get Posts Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['localPosts'] ?? [];

        } catch (Exception $e) {
            Log::error('GMB Get Posts Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete a local post
     * 
     * @param string $postName
     * @return bool
     */
    public function deletePost(string $postName): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->delete(self::POSTS_API . "/{$postName}");

            if ($response->failed()) {
                Log::error('GMB Delete Post Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('GMB Delete Post Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get location information
     * 
     * @return array|null
     */
    public function getLocationInfo(): ?array
    {
        $locationName = $this->getLocationName();

        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::BUSINESS_INFO_API . "/{$locationName}", [
                    'readMask' => 'name,title,phoneNumbers,categories,storefrontAddress,websiteUri,regularHours,metadata'
                ]);

            if ($response->failed()) {
                Log::error('GMB Get Location Info Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('GMB Get Location Info Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
