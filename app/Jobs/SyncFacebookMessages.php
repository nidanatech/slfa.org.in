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

class SyncFacebookMessages implements ShouldQueue
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
            $conversations = $service->getConversations();

            Log::info('Facebook Messages Sync Started', [
                'channel_id' => $this->channel->id,
                'conversation_count' => count($conversations)
            ]);

            foreach ($conversations as $fbConversation) {
                $this->processConversation($fbConversation, $service);
            }

            Log::info('Facebook Messages Sync Completed', [
                'channel_id' => $this->channel->id
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Messages Sync Failed', [
                'channel_id' => $this->channel->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function processConversation(array $fbConversation, FacebookService $service): void
    {
        $conversationId = $fbConversation['id'] ?? null;
        if (!$conversationId) {
            return;
        }

        // Get full messages for this conversation
        $messages = $service->getConversationMessages($conversationId);
        if (empty($messages)) {
            return;
        }

        // Get the first message to determine the contact
        $firstMessage = end($messages); // Oldest message first
        $senderId = $firstMessage['from']['id'] ?? null;
        
        if (!$senderId || $senderId == $this->channel->config['page_id']) {
            // Skip if no sender or sender is the page itself
            return;
        }

        // Get user profile
        $userProfile = $service->getUserProfile($senderId);
        
        // Find or create contact
        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $this->channel->tenant_id,
                'name' => $userProfile['name'] ?? 'Facebook User',
            ],
            [
                'avatar_url' => $userProfile['profile_pic'] ?? null,
                'source' => 'facebook_messenger',
            ]
        );

        // Find or create conversation
        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $this->channel->tenant_id,
                'channel_id' => $this->channel->id,
                'contact_id' => $contact->id,
                'conversation_type' => 'facebook_message',
            ],
            [
                'status' => 'open',
                'last_message_at' => $fbConversation['updated_time'] ?? now(),
            ]
        );

        // Process each message
        foreach ($messages as $message) {
            $this->processMessage($message, $conversation);
        }
    }

    protected function processMessage(array $fbMessage, Conversation $conversation): void
    {
        $messageId = $fbMessage['id'] ?? null;
        $messageText = $fbMessage['message'] ?? '';
        $createdTime = $fbMessage['created_time'] ?? now();
        $fromId = $fbMessage['from']['id'] ?? null;
        
        // Determine direction
        $direction = ($fromId == $this->channel->config['page_id']) ? 'outbound' : 'inbound';

        // Check if message already exists
        $exists = Message::where('conversation_id', $conversation->id)
            ->where('meta->message_id', $messageId)
            ->exists();

        if ($exists) {
            return; // Skip if already synced
        }

        // Handle attachments
        $attachments = $fbMessage['attachments'] ?? [];
        $attachmentData = [];
        
        foreach ($attachments as $attachment) {
            $attachmentData[] = [
                'type' => $attachment['type'] ?? 'unknown',
                'url' => $attachment['image_data']['url'] ?? $attachment['video_data']['url'] ?? $attachment['file_url'] ?? null,
            ];
        }

        // Create message
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => $messageText,
            'direction' => $direction,
            'created_at' => $createdTime,
            'meta' => [
                'message_id' => $messageId,
                'type' => 'facebook_message',
                'attachments' => $attachmentData,
            ],
        ]);

        // Update conversation last_message_at
        $conversation->update([
            'last_message_at' => $createdTime,
        ]);
    }
}
