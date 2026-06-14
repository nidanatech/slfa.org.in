<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Channel;
use App\Models\GmbReview;
use App\Models\GmbQuestion;
use App\Http\Controllers\GmbInboxHelper;

class ProcessGmbWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    /**
     * Create a new job instance.
     *
     * @param array $message The Pub/Sub message payload
     */
    public function __construct(array $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (!isset($this->message['data'])) {
                Log::warning('ProcessGmbWebhookJob: No data in message', ['message' => $this->message]);
                return;
            }

            // 1. Decode Payload
            $jsonPayload = base64_decode($this->message['data']);
            $data = json_decode($jsonPayload, true);

            if (!$data) {
                Log::error('ProcessGmbWebhookJob: Failed to decode JSON', ['raw' => $this->message['data']]);
                return;
            }

            Log::info('ProcessGmbWebhookJob Processing', ['data' => $data]);

            // Expected format: { "notificationType": "NEW_REVIEW", "name": "accounts/.../reviews/..." }
            $type = $data['notificationType'] ?? null;
            $resourceName = $data['name'] ?? null;

            if (!$type || !$resourceName) {
                Log::warning('ProcessGmbWebhookJob: Missing type or name', ['data' => $data]);
                return;
            }

            // 2. Extract Location ID to find the Channel
            // Resource name pattern: accounts/{accountId}/locations/{locationId}/...
            if (!preg_match('/locations\/([^\/]+)\//', $resourceName, $matches)) {
                Log::warning('ProcessGmbWebhookJob: Could not parse locationId', ['name' => $resourceName]);
                return;
            }
            // Location ID in our config is likely just the numeric part or full "locations/..." string
            // In GoogleBusinessChannelController, we store 'location_name' as "locations/123..."
            // So we can search for that.
            
            // Reconstruct the potential stored location name
            // The regex extracted the ID part. matches[1] is the ID.
            $locationId = $matches[1];
            $locationName = "locations/{$locationId}";

            Log::info('ProcessGmbWebhookJob: Finding channel for location', ['location' => $locationName]);

            // Database Search: Find Channel where config->location_name matches
            // We need to use JSON query matching
            $channel = Channel::where('type', 'google_business')
                ->whereJsonContains('config->location_name', $locationName)
                ->first();

            if (!$channel) {
                Log::warning('ProcessGmbWebhookJob: No channel found for location', ['location' => $locationName]);
                return;
            }

            // 3. Process based on Type
            switch ($type) {
                case 'NEW_REVIEW':
                    $this->processReview($channel, $resourceName);
                    break;
                case 'UPDATED_REVIEW':
                    $this->processReview($channel, $resourceName);
                    break;
                case 'NEW_QUESTION':
                    $this->processQuestion($channel, $resourceName);
                    break;
                case 'UPDATED_QUESTION':
                    $this->processQuestion($channel, $resourceName); // Treat update same as new fetch
                    break; 
                default:
                    Log::info('ProcessGmbWebhookJob: Unhandled type', ['type' => $type]);
                    break;
            }

        } catch (\Exception $e) {
            Log::error('ProcessGmbWebhookJob Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't rethrow to avoid infinite loops on bad payloads, unless transient.
        }
    }

    private function processReview(Channel $channel, string $resourceName)
    {
        // GMB v4 API URL for a specific review
        // Resource name is typically "accounts/{acc}/locations/{loc}/reviews/{rev}"
        // API Base: https://mybusiness.googleapis.com/v4/
        $url = "https://mybusiness.googleapis.com/v4/" . $resourceName;
        
        $accessToken = $channel->config['access_token'] ?? null;
        if (!$accessToken) {
             Log::error('ProcessGmbWebhookJob: Channel has no access token', ['channel_id' => $channel->id]);
             return;
        }

        $response = Http::withToken($accessToken)->get($url);

        if ($response->failed()) {
            Log::error('ProcessGmbWebhookJob: Failed to fetch review', [
                'status' => $response->status(), 
                'body' => $response->body()
            ]);
            return;
        }

        $review = $response->json();
        
        // Use same logic as Controller to store GmbReview
        // Extract ID (last part of resource name)
        $parts = explode('/', $resourceName);
        $reviewId = end($parts);

        // Map Rating
        $starRating = 0;
        if (isset($review['starRating'])) {
             $ratingMap = [
                'FIVE' => 5, 'FOUR' => 4, 'THREE' => 3, 'TWO' => 2, 'ONE' => 1
            ];
            $val = strtoupper((string)$review['starRating']);
            $starRating = $ratingMap[$val] ?? (is_numeric($val) ? (int)$val : 0);
        }

        $gmbReview = GmbReview::updateOrCreate(
            ['review_id' => $reviewId],
            [
                'channel_id' => $channel->id,
                'reviewer_display_name' => $review['reviewer']['displayName'] ?? null,
                'reviewer_profile_photo_url' => $review['reviewer']['profilePhotoUrl'] ?? null,
                'star_rating' => $starRating,
                'comment' => $review['comment'] ?? null,
                'review_reply' => $review['reviewReply']['comment'] ?? null,
                'reply_update_time' => $review['reviewReply']['updateTime'] ?? null,
                'create_time' => $review['createTime'] ?? null,
                'update_time' => $review['updateTime'] ?? null,
            ]
        );

        // Sync to Inbox
        $reviewerEmail = $review['reviewer']['email'] ?? null; // Rare but possible
        
        // Determine isNewReview (created recently or just updated?)
        // Since we did updateOrCreate, checking wasRecentlyCreated is accurate for DB status
        $isNew = $gmbReview->wasRecentlyCreated;

        GmbInboxHelper::syncReviewToInbox($gmbReview, $channel, $reviewerEmail, $isNew);
        
        Log::info('ProcessGmbWebhookJob: Review processed', ['review_id' => $reviewId]);
    }

    private function processQuestion(Channel $channel, string $resourceName)
    {
        // Resource name: accounts/{acc}/locations/{loc}/questions/{id}
        $url = "https://mybusiness.googleapis.com/v4/" . $resourceName;
        
        $response = Http::withToken($channel->config['access_token'])->get($url);

        if ($response->failed()) {
             Log::error('ProcessGmbWebhookJob: Failed to fetch question', ['status' => $response->status()]);
             return;
        }

        $question = $response->json();
        $parts = explode('/', $resourceName);
        $questionId = end($parts);
        
        // Handle Answer text extraction if available
        $topAnswer = $question['topAnswers'][0]['answerText'] ?? null;

        $gmbQuestion = GmbQuestion::updateOrCreate(
            ['question_id' => $questionId],
            [
                'channel_id' => $channel->id,
                'author_display_name' => $question['author']['displayName'] ?? null,
                'author_profile_photo_url' => $question['author']['profilePhotoUrl'] ?? null,
                'text' => $question['text'] ?? null,
                'upvote_count' => $question['upvoteCount'] ?? 0,
                'answer_text' => $topAnswer,
                'create_time' => $question['createTime'] ?? null,
                'update_time' => $question['updateTime'] ?? null,
            ]
        );

         // Sync to Inbox
         // Assuming Helper has syncQuestionToInbox
         GmbInboxHelper::syncQuestionToInbox($gmbQuestion, $channel);
         Log::info('ProcessGmbWebhookJob: Question processed', ['question_id' => $questionId]);
    }
}
