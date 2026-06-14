<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\SocialPost;
use App\Services\GoogleBusinessService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SocialMediaService
{
    /**
     * Publish post to Facebook
     */
    public function publishToFacebook(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        $accessToken = $channel->access_token;
        $pageId = $channel->platform_user_id;

        $endpoint = "https://graph.facebook.com/v18.0/{$pageId}/feed";
        
        $data = [
            'message' => $post->content,
            'access_token' => $accessToken,
        ];

        // Handle media attachments
        if (!empty($mediaPaths)) {
            $mediaIds = $this->uploadFacebookMedia($mediaPaths, $accessToken, $pageId);
            $data['attached_media'] = $mediaIds;
        }

        $response = Http::post($endpoint, $data);
        
        if ($response->successful()) {
            $result = $response->json();
            return [
                'id' => $result['id'] ?? null,
                'url' => "https://facebook.com/{$result['id']}",
            ];
        }

        throw new \Exception($response->json()['error']['message'] ?? 'Facebook publishing failed');
    }

    /**
     * Publish post to Instagram
     */
    public function publishToInstagram(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        if (empty($mediaPaths)) {
            throw new \Exception('Instagram posts require at least one image or video');
        }

        $accessToken = $channel->access_token;
        $igUserId = $channel->platform_user_id;

        // Step 1: Create media container
        $mediaUrl = $mediaPaths[0]; // Instagram supports single media
        $endpoint = "https://graph.facebook.com/v18.0/{$igUserId}/media";
        
        $containerResponse = Http::post($endpoint, [
            'image_url' => $mediaUrl,
            'caption' => $post->content,
            'access_token' => $accessToken,
        ]);

        if (!$containerResponse->successful()) {
            throw new \Exception('Failed to create Instagram media container');
        }

        $containerId = $containerResponse->json()['id'];

        // Step 2: Publish the container
        $publishEndpoint = "https://graph.facebook.com/v18.0/{$igUserId}/media_publish";
        $publishResponse = Http::post($publishEndpoint, [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->successful()) {
            $result = $publishResponse->json();
            return [
                'id' => $result['id'],
                'url' => "https://instagram.com/p/{$result['id']}",
            ];
        }

        throw new \Exception('Instagram publishing failed');
    }

    /**
     * Publish post to LinkedIn
     */
    public function publishToLinkedIn(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        $accessToken = $channel->access_token;
        $personUrn = $channel->platform_user_id;

        $endpoint = "https://api.linkedin.com/v2/ugcPosts";
        
        $data = [
            'author' => "urn:li:person:{$personUrn}",
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => empty($mediaPaths) ? 'NONE' : 'IMAGE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post($endpoint, $data);

        if ($response->successful()) {
            $result = $response->json();
            return [
                'id' => $result['id'],
                'url' => "https://linkedin.com/feed/update/{$result['id']}",
            ];
        }

        throw new \Exception('LinkedIn publishing failed');
    }

    /**
     * Publish post to Twitter/X
     */
    public function publishToTwitter(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        $accessToken = $channel->access_token;
        $endpoint = "https://api.twitter.com/2/tweets";
        
        $data = ['text' => $post->content];

        // Handle media if present
        if (!empty($mediaPaths)) {
            $mediaIds = $this->uploadTwitterMedia($mediaPaths, $accessToken);
            $data['media'] = ['media_ids' => $mediaIds];
        }

        $response = Http::withToken($accessToken)->post($endpoint, $data);

        if ($response->successful()) {
            $result = $response->json();
            $tweetId = $result['data']['id'];
            return [
                'id' => $tweetId,
                'url' => "https://twitter.com/i/web/status/{$tweetId}",
            ];
        }

        throw new \Exception('Twitter publishing failed');
    }

    /**
     * Publish post to TikTok
     */
    public function publishToTikTok(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        // TikTok requires video content
        if (empty($mediaPaths)) {
            throw new \Exception('TikTok posts require a video');
        }

        $accessToken = $channel->access_token;
        $endpoint = "https://open-api.tiktok.com/share/video/upload/";
        
        // TikTok API implementation would go here
        // This is a placeholder as TikTok's API is more complex
        
        throw new \Exception('TikTok publishing not yet implemented');
    }

    /**
     * Publish post to Google Business Profile
     */
    public function publishToGoogleBusiness(Channel $channel, SocialPost $post, array $mediaPaths = []): array
    {
        $service = new GoogleBusinessService($channel);
        
        // Prepare post data
        $postData = [
            'languageCode' => 'en-US',
            'summary' => $post->content,
            'topicType' => 'STANDARD', // Can be STANDARD, EVENT, OFFER, PRODUCT
        ];

        // Handle media if present
        if (!empty($mediaPaths)) {
            $media = [];
            
            foreach ($mediaPaths as $path) {
                // Determine if it's a photo or video
                $mimeType = mime_content_type($path);
                $mediaFormat = str_starts_with($mimeType, 'video/') ? 'VIDEO' : 'PHOTO';
                
                // Upload media to Google
                $uploadedMedia = $service->uploadMedia(
                    Storage::url($path), 
                    $mediaFormat
                );
                
                if ($uploadedMedia) {
                    $media[] = [
                        'mediaFormat' => $mediaFormat,
                        'sourceUrl' => $uploadedMedia['googleUrl'] ?? Storage::url($path),
                    ];
                }
            }
            
            if (!empty($media)) {
                $postData['media'] = $media;
            }
        }

        // Add call-to-action if specified in post meta
        if (isset($post->metadata['call_to_action'])) {
            $postData['callToAction'] = [
                'actionType' => $post->metadata['call_to_action']['type'] ?? 'LEARN_MORE',
                'url' => $post->metadata['call_to_action']['url'] ?? '',
            ];
        }

        // Create the post
        $result = $service->createPost($postData);
        
        if (!$result) {
            throw new \Exception('Failed to create Google Business post');
        }

        return [
            'id' => $result['name'] ?? 'unknown',
            'url' => null, // GMB doesn't provide direct post URLs
        ];
    }

    /**
     * Helper: Upload media to Facebook
     */
    private function uploadFacebookMedia(array $mediaPaths, string $accessToken, string $pageId): array
    {
        $mediaIds = [];
        
        foreach ($mediaPaths as $path) {
            $endpoint = "https://graph.facebook.com/v18.0/{$pageId}/photos";
            $response = Http::attach('source', file_get_contents($path))
                ->post($endpoint, [
                    'access_token' => $accessToken,
                    'published' => false,
                ]);

            if ($response->successful()) {
                $mediaIds[] = ['media_fbid' => $response->json()['id']];
            }
        }

        return $mediaIds;
    }

    /**
     * Helper: Upload media to Twitter
     */
    private function uploadTwitterMedia(array $mediaPaths, string $accessToken): array
    {
        $mediaIds = [];
        $endpoint = "https://upload.twitter.com/1.1/media/upload.json";
        
        foreach ($mediaPaths as $path) {
            $response = Http::withToken($accessToken)
                ->attach('media', file_get_contents($path))
                ->post($endpoint);

            if ($response->successful()) {
                $mediaIds[] = $response->json()['media_id_string'];
            }
        }

        return $mediaIds;
    }
}
