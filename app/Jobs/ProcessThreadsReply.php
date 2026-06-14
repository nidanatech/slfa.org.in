<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ThreadsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessThreadsReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $threadsUserId;
    protected array $replyData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $threadsUserId, array $replyData)
    {
        $this->threadsUserId = $threadsUserId;
        $this->replyData = $replyData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessThreadsReply: Job started', [
            'threads_user_id' => $this->threadsUserId,
            'reply_data_keys' => array_keys($this->replyData)
        ]);

        try {
            // Find the channel by Threads user ID
            $channel = Channel::where('type', 'threads')
                ->where('config->user_id', $this->threadsUserId)
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::warning('ProcessThreadsReply: Channel not found', [
                    'threads_user_id' => $this->threadsUserId
                ]);
                return;
            }

            Log::info('ProcessThreadsReply: Channel found', [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name
            ]);

            // Extract reply data
            $replyId = $this->replyData['id'] ?? null;
            $text = $this->replyData['text'] ?? '';
            $mediaUrl = $this->replyData['media_url'] ?? null;
            $parentId = $this->replyData['parent_id'] ?? $this->replyData['root_post'] ?? null;
            $timestamp = $this->replyData['timestamp'] ?? now()->toIso8601String();
            
            // Reply author info
            $fromId = $this->replyData['from']['id'] ?? null;
            $fromUsername = $this->replyData['from']['username'] ?? $this->replyData['username'] ?? 'unknown';
            $fromName = $this->replyData['from']['name'] ?? $fromUsername;

            if (!$replyId || !$fromId) {
                Log::error('ProcessThreadsReply: Missing required data', [
                    'reply_id' => $replyId,
                    'from_id' => $fromId
                ]);
                return;
            }

            Log::info('ProcessThreadsReply: Extracted reply data', [
                'reply_id' => $replyId,
                'from_username' => $fromUsername,
                'parent_id' => $parentId
            ]);

            $nameParts = Contact::splitName($fromName);

            // Create or update contact
            $contact = Contact::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_id' => $fromId
                ],
                [
                    'tenant_id' => $channel->tenant_id,
                    'name' => $fromName,
                    'first_name' => $nameParts['first_name'],
                    'last_name' => $nameParts['last_name'],
                    'email' => null,
                    'phone' => null,
                    'metadata' => [
                        'username' => $fromUsername,
                        'platform' => 'threads'
                    ]
                ]
            );

            Log::info('ProcessThreadsReply: Contact found/created', [
                'contact_id' => $contact->id,
                'contact_name' => $contact->name
            ]);

            // Find or create conversation for the parent thread
            $conversation = Conversation::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_id' => $parentId // Use parent thread ID as conversation identifier
                ],
                [
                    'contact_id' => $contact->id,
                    'subject' => 'Thread Conversation',
                    'status' => 'open',
                    'metadata' => [
                        'platform' => 'threads',
                        'type' => 'reply',
                        'parent_thread_id' => $parentId
                    ]
                ]
            );

            // Update conversation contact if this is a new message from a different user
            if ($conversation->contact_id !== $contact->id) {
                $conversation->update(['contact_id' => $contact->id]);
            }

            Log::info('ProcessThreadsReply: Conversation found/created', [
                'conversation_id' => $conversation->id,
                'external_id' => $conversation->external_id
            ]);

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'external_id' => $replyId,
                'direction' => 'inbound',
                'content' => $text,
                'metadata' => [
                    'platform' => 'threads',
                    'media_url' => $mediaUrl,
                    'username' => $fromUsername,
                    'timestamp' => $timestamp,
                    'raw_data' => $this->replyData
                ],
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]);

            Log::info('ProcessThreadsReply: Message created', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id
            ]);

            // Update conversation updated_at
            $conversation->touch();

            Log::info('ProcessThreadsReply: SUCCESS', [
                'channel_id' => $channel->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessThreadsReply: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'threads_user_id' => $this->threadsUserId
            ]);
        }
    }
}
