<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFacebookComments implements ShouldQueue
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
            $service = new FacebookService($this->channel);
            
            // Get recent posts
            $posts = $service->getPagePosts();

            Log::info('Facebook Comments Sync Started', [
                'channel_id' => $this->channel->id,
                'post_count' => count($posts)
            ]);

            foreach ($posts as $post) {
                $this->processPostComments($post, $service);
            }

            Log::info('Facebook Comments Sync Completed', [
                'channel_id' => $this->channel->id
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Comments Sync Failed', [
                'channel_id' => $this->channel->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function processPostComments(array $post, FacebookService $service): void
    {
        $postId = $post['id'] ?? null;
        if (!$postId) {
            return;
        }

        // Get comments for this post
        $comments = $service->getPostComments($postId);
        
        if (empty($comments)) {
            return;
        }

        // Create one conversation per post (all comments in one thread)
        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $this->channel->tenant_id,
                'channel_id' => $this->channel->id,
                'conversation_type' => 'facebook_comment',
            ],
            [
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );

        // Update conversation meta with post info
        $conversation->update([
            'meta' => array_merge($conversation->meta ?? [], [
                'post_id' => $postId,
                'post_message' => $post['message'] ?? '',
                'post_url' => $post['permalink_url'] ?? null,
            ])
        ]);

        // Process each comment
        foreach ($comments as $comment) {
            $this->processComment($comment, $conversation, $service);
        }
    }

    protected function processComment(array $fbComment, Conversation $conversation, FacebookService $service): void
    {
        $commentId = $fbComment['id'] ?? null;
        $commentText = $fbComment['message'] ?? '';
        $createdTime = $fbComment['created_time'] ?? now();
        $fromId = $fbComment['from']['id'] ?? null;
        $fromName = $fbComment['from']['name'] ?? 'Facebook User';
        
        if (!$commentId) {
            return;
        }

        // Check if comment already exists
        $exists = Message::where('conversation_id', $conversation->id)
            ->where('meta->comment_id', $commentId)
            ->exists();

        if ($exists) {
            return; // Skip if already synced
        }

        // Determine direction (page comments are outbound, user comments are inbound)
        $direction = ($fromId == $this->channel->config['page_id']) ? 'outbound' : 'inbound';

        // If inbound, create/update contact
        $contactId = null;
        if ($direction === 'inbound') {
            $contact = Contact::firstOrCreate(
                [
                    'tenant_id' => $this->channel->tenant_id,
                    'name' => $fromName,
                ],
                [
                    'source' => 'facebook_comment',
                ]
            );
            $contactId = $contact->id;
            
            // Update conversation contact if not set
            if (!$conversation->contact_id) {
                $conversation->update(['contact_id' => $contactId]);
            }
        }

        // Handle attachment
        $attachment = $fbComment['attachment'] ?? null;
        $attachmentData = null;
        
        if ($attachment) {
            $attachmentData = [
                'type' => $attachment['type'] ?? 'unknown',
                'url' => $attachment['media']['image']['src'] ?? $attachment['url'] ?? null,
            ];
        }

        // Create message
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => $commentText,
            'direction' => $direction,
            'created_at' => $createdTime,
            'meta' => [
                'comment_id' => $commentId,
                'type' => 'facebook_comment',
                'parent_comment_id' => $fbComment['parent']['id'] ?? null,
                'attachment' => $attachmentData,
                'like_count' => $fbComment['like_count'] ?? 0,
                'permalink_url' => $fbComment['permalink_url'] ?? null,
            ],
        ]);

        // Update conversation last_message_at
        $conversation->update([
            'last_message_at' => $createdTime,
        ]);
    }
}
