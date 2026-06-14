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

class ProcessFacebookComment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $pageId;
    protected array $commentData;

    public function __construct(string $pageId, array $commentData)
    {
        $this->pageId = $pageId;
        $this->commentData = $commentData;
    }

    public function handle(): void
    {
        try {
            // Find channel by page_id
            $channel = Channel::where('type', 'messenger')
                ->where('config->page_id', $this->pageId)
                ->first();

            if (!$channel) {
                Log::warning('Facebook channel not found for page', ['page_id' => $this->pageId]);
                return;
            }

            $commentId = $this->commentData['comment_id'] ?? null;
            $postId = $this->commentData['post_id'] ?? null;
            $verb = $this->commentData['verb'] ?? 'add';

            if (!$commentId || !$postId) {
                return;
            }

            // Handle comment deletion
            if ($verb === 'remove') {
                Message::where('meta->comment_id', $commentId)->delete();
                return;
            }

            $service = new FacebookService($channel);

            // Fetch comment details
            $comments = $service->getPostComments($postId);
            $comment = collect($comments)->firstWhere('id', $commentId);

            if (!$comment) {
                return;
            }

            // Find or create conversation for this post
            $conversation = Conversation::firstOrCreate(
                [
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'conversation_type' => 'facebook_comment',
                ],
                [
                    'status' => 'open',
                    'last_message_at' => now(),
                    'meta' => [
                        'post_id' => $postId,
                    ]
                ]
            );

            // Check if comment already exists
            $exists = Message::where('conversation_id', $conversation->id)
                ->where('meta->comment_id', $commentId)
                ->exists();

            if ($exists && $verb === 'edit') {
                // Update existing comment
                Message::where('conversation_id', $conversation->id)
                    ->where('meta->comment_id', $commentId)
                    ->update([
                        'content' => $comment['message'] ?? '',
                    ]);
                return;
            }

            if ($exists) {
                return; // Skip duplicate
            }

            // Determine direction
            $fromId = $comment['from']['id'] ?? null;
            $direction = ($fromId == $this->pageId) ? 'outbound' : 'inbound';

            // Create contact for commenter if inbound
            if ($direction === 'inbound') {
                $contact = Contact::firstOrCreate(
                    [
                        'tenant_id' => $channel->tenant_id,
                        'name' => $comment['from']['name'] ?? 'Facebook User',
                    ],
                    [
                        'source' => 'facebook_comment',
                    ]
                );

                if (!$conversation->contact_id) {
                    $conversation->update(['contact_id' => $contact->id]);
                }
            }

            // Create message
            Message::create([
                'conversation_id' => $conversation->id,
                'content' => $comment['message'] ?? '',
                'direction' => $direction,
                'created_at' => $comment['created_time'] ?? now(),
                'meta' => [
                    'comment_id' => $commentId,
                    'type' => 'facebook_comment',
                    'parent_comment_id' => $comment['parent']['id'] ?? null,
                    'like_count' => $comment['like_count'] ?? 0,
                ],
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'status' => 'open',
            ]);

            Log::info('Facebook comment processed', [
                'conversation_id' => $conversation->id,
                'comment_id' => $commentId
            ]);

        } catch (\Exception $e) {
            Log::error('Process Facebook Comment Failed', [
                'error' => $e->getMessage(),
                'page_id' => $this->pageId
            ]);
        }
    }
}
