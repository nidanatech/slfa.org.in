<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessThreadsMention implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $threadsUserId;
    protected array $mentionData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $threadsUserId, array $mentionData)
    {
        $this->threadsUserId = $threadsUserId;
        $this->mentionData = $mentionData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessThreadsMention: Job started', [
            'threads_user_id' => $this->threadsUserId,
            'mention_data_keys' => array_keys($this->mentionData)
        ]);

        try {
            // Find the channel by Threads user ID
            $channel = Channel::where('type', 'threads')
                ->where('config->user_id', $this->threadsUserId)
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::warning('ProcessThreadsMention: Channel not found', [
                    'threads_user_id' => $this->threadsUserId
                ]);
                return;
            }

            Log::info('ProcessThreadsMention: Channel found', [
                'channel_id' => $channel->id
            ]);

            // Extract mention data
            $mentionId = $this->mentionData['id'] ?? null;
            $text = $this->mentionData['text'] ?? '';
            $mediaUrl = $this->mentionData['media_url'] ?? null;
            $timestamp = $this->mentionData['timestamp'] ?? now()->toIso8601String();
            
            // Mention author info
            $fromId = $this->mentionData['from']['id'] ?? $this->mentionData['media_owner'] ?? null;
            $fromUsername = $this->mentionData['from']['username'] ?? $this->mentionData['username'] ?? 'unknown';
            $fromName = $this->mentionData['from']['name'] ?? $fromUsername;

            if (!$mentionId || !$fromId) {
                Log::error('ProcessThreadsMention: Missing required data', [
                    'mention_id' => $mentionId,
                    'from_id' => $fromId
                ]);
                return;
            }

            Log::info('ProcessThreadsMention: Extracted mention data', [
                'mention_id' => $mentionId,
                'from_username' => $fromUsername
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

            Log::info('ProcessThreadsMention: Contact found/created', [
                'contact_id' => $contact->id
            ]);

            // Create conversation for the mention
            $conversation = Conversation::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_id' => $mentionId
                ],
                [
                    'contact_id' => $contact->id,
                    'subject' => 'Mention: @' . ($channel->config['username'] ?? ''),
                    'status' => 'open',
                    'metadata' => [
                        'platform' => 'threads',
                        'type' => 'mention',
                        'mentioned_in' => $mentionId
                    ]
                ]
            );

            Log::info('ProcessThreadsMention: Conversation found/created', [
                'conversation_id' => $conversation->id
            ]);

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'external_id' => $mentionId,
                'direction' => 'inbound',
                'content' => $text,
                'metadata' => [
                    'platform' => 'threads',
                    'media_url' => $mediaUrl,
                    'username' => $fromUsername,
                    'timestamp' => $timestamp,
                    'type' => 'mention',
                    'raw_data' => $this->mentionData
                ],
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]);

            Log::info('ProcessThreadsMention: Message created', [
                'message_id' => $message->id
            ]);

            // Update conversation
            $conversation->touch();

            Log::info('ProcessThreadsMention: SUCCESS', [
                'channel_id' => $channel->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessThreadsMention: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
