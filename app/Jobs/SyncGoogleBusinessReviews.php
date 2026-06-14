<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GoogleBusinessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoogleBusinessReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Channel $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function handle(): void
    {
        try {
            $service = new GoogleBusinessService($this->channel);
            $reviews = $service->getReviews();

            Log::info('GMB Reviews Sync Started', [
                'channel_id' => $this->channel->id,
                'review_count' => count($reviews)
            ]);

            foreach ($reviews as $review) {
                $this->processReview($review);
            }

            Log::info('GMB Reviews Sync Completed', [
                'channel_id' => $this->channel->id
            ]);

        } catch (\Exception $e) {
            Log::error('GMB Reviews Sync Failed', [
                'channel_id' => $this->channel->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function processReview(array $review): void
    {
        $reviewId = $review['reviewId'] ?? null;
        $reviewName = $review['name'] ?? null; // Full resource name
        
        if (!$reviewId || !$reviewName) {
            return;
        }

        // Extract reviewer information
        $reviewer = $review['reviewer'] ?? [];
        $reviewerName = $reviewer['displayName'] ?? 'Anonymous';
        $reviewerProfileUrl = $reviewer['profilePhotoUrl'] ?? null;
        
        // Review content
        $comment = $review['comment'] ?? '';
        $starRating = $review['starRating'] ?? null;
        $createTime = $review['createTime'] ?? now()->toIso8601String();
        
        // Owner reply (if exists)
        $reviewReply = $review['reviewReply'] ?? null;
        $replyComment = $reviewReply['comment'] ?? null;
        $replyUpdateTime = $reviewReply['updateTime'] ?? null;

        // Find or create contact for reviewer
        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $this->channel->tenant_id,
                'name' => $reviewerName,
            ],
            [
                'avatar_url' => $reviewerProfileUrl,
                'source' => 'google_business_review',
            ]
        );

        // Find or create conversation for this review
        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $this->channel->tenant_id,
                'channel_id' => $this->channel->id,
                'contact_id' => $contact->id,
                'conversation_type' => 'review', // Mark as review type
            ],
            [
                'status' => $replyComment ? 'closed' : 'open',
                'last_message_at' => $createTime,
            ]
        );

        // Create or update the review message
        $starEmoji = str_repeat('⭐', (int)($starRating ?? 0));
        $messageContent = "{$starEmoji}\n\n{$comment}";

        Message::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'meta->review_id' => $reviewId,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'content' => $messageContent,
                'created_at' => $createTime,
                'meta' => [
                    'review_id' => $reviewId,
                    'review_name' => $reviewName,
                    'star_rating' => $starRating,
                    'type' => 'google_business_review',
                ],
            ]
        );

        // If there's an owner reply, create/update it as an outbound message
        if ($replyComment) {
            Message::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'meta->review_reply' => true,
                ],
                [
                    'tenant_id' => $this->channel->tenant_id,
                    'content' => $replyComment,
                    'created_at' => $replyUpdateTime ?? now(),
                    'meta' => [
                        'review_reply' => true,
                        'review_name' => $reviewName,
                        'type' => 'google_business_review_reply',
                    ],
                ]
            );

            // Update conversation status
            $conversation->update([
                'status' => 'closed',
                'last_message_at' => $replyUpdateTime ?? now(),
            ]);
        }
    }
}
