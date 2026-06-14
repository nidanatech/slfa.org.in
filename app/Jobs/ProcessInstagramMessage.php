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
use Illuminate\Support\Facades\Http;

class ProcessInstagramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $pageId;
    protected array $event;

    public function __construct(string $pageId, array $event)
    {
        $this->pageId = $pageId;
        $this->event = $event;
    }

    public function handle(): void
    {
        Log::info('ProcessInstagramMessage: Job started', [
            'page_id' => $this->pageId,
            'event_keys' => array_keys($this->event)
        ]);

        try {
            // Find Instagram channel by page ID or Instagram ID
            $channel = Channel::where('type', 'instagram')
                ->where(function ($q) {
                    $q->where('config->page_id', $this->pageId)
                      ->orWhere('config->instagram_id', $this->pageId)
                      ->orWhere('config->user_id', $this->pageId);
                })
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::warning('ProcessInstagramMessage: Channel not found', [
                    'page_id' => $this->pageId
                ]);
                return;
            }

            Log::info('ProcessInstagramMessage: Channel found', [
                'channel_id' => $channel->id
            ]);

            // Extract message data
            $sender = $this->event['sender'] ?? [];
            $recipient = $this->event['recipient'] ?? [];
            $message = $this->event['message'] ?? [];
            $timestamp = $this->event['timestamp'] ?? now()->timestamp;

            $senderId = $sender['id'] ?? null;
            $messageId = $message['mid'] ?? null;
            $messageText = $message['text'] ?? '';
            
            // Process attachments into normalized format
            $rawAttachments = $message['attachments'] ?? [];
            $attachments = [];
            
            foreach ($rawAttachments as $attachment) {
                $payload = $attachment['payload'] ?? [];
                $attachments[] = [
                    'type' => $attachment['type'] ?? 'unknown',
                    'url' => $payload['url'] ?? null,
                ];
            }

            if (!$senderId || !$messageId) {
                Log::error('ProcessInstagramMessage: Missing required data', [
                    'sender_id' => $senderId,
                    'message_id' => $messageId
                ]);
                return;
            }

            Log::info('ProcessInstagramMessage: Extracted message data', [
                'sender_id' => $senderId,
                'message_id' => $messageId
            ]);

            // Fetch User Profile
            $profilePic = null;
            $displayName = 'Instagram User';
            $fullName = null;
            $userName = null;

            try {
                $service = new \App\Services\InstagramService($channel);
                $userData = $service->getUserInfo($senderId);
                
                if ($userData) {
                    $profilePic = $userData['profile_picture_url'] ?? null;
                    $userName = $userData['username'] ?? null;
                    $fullName = $userData['name'] ?? null;
                    
                    Log::info('ProcessInstagramMessage: Fetched user profile', ['data' => $userData]);
                }
            } catch (\Exception $e) {
                Log::warning('ProcessInstagramMessage: Failed to fetch user profile', ['error' => $e->getMessage()]);
            }

            $displayName = $fullName ?? ($userName ?? 'Instagram User');
            $nameParts = Contact::splitName($displayName);

            // Create or update contact
            $contact = Contact::ensureContact($channel, $senderId, [
                'name' => $displayName,
                'avatar_url' => $profilePic,
                'metadata' => [
                    'platform' => 'instagram',
                    'ig_scoped_id' => $senderId
                ]
            ]);

            // Additional Profile Update Policy: Update placeholder names and avatars if we have better info now
            $updates = [];
            $placeholders = ['Instagram User', 'unknown', 'Instagram Business Account'];
            
            if (in_array($contact->name, $placeholders) && !in_array($displayName, $placeholders)) {
                $updates['name'] = $displayName;
                $updates['first_name'] = $nameParts['first_name'];
                $updates['last_name'] = $nameParts['last_name'];
            }

            if ($profilePic && $contact->avatar_url !== $profilePic) {
                $updates['avatar_url'] = $profilePic;
            }

            if (!empty($updates)) {
                $contact->update($updates);
                Log::info('ProcessInstagramMessage: Contact updated with better info', [
                    'contact_id' => $contact->id,
                    'updates' => array_keys($updates)
                ]);
            }

            Log::info('ProcessInstagramMessage: Contact found/created', [
                'contact_id' => $contact->id
            ]);

            // Deduplication Check
            $existingMessage = Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })->where('meta->message_id', $messageId)->first();

            if ($existingMessage) {
                Log::info('ProcessInstagramMessage: Message already exists, skipping creation', [
                    'message_id' => $messageId,
                    'internal_id' => $existingMessage->id
                ]);
                return;
            }

            // Find or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'contact_id' => $contact->id,
                    'conversation_type' => 'instagram_message',
                ],
                [
                    'status' => 'open',
                    'last_message_at' => now(),
                ]
            );

            // Update conversation contact if different
            if ($conversation->contact_id !== $contact->id) {
                $conversation->update(['contact_id' => $contact->id]);
            }

            Log::info('ProcessInstagramMessage: Conversation found/created', [
                'conversation_id' => $conversation->id
            ]);

            // Create message
            $messageData = [
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'sender_type' => 'contact',
                'content' => $messageText,
                'status' => 'read',
                'meta' => [
                    'message_id' => $messageId,
                    'platform' => 'instagram',
                    'attachments' => $attachments,
                    'raw_event' => $this->event
                ],
                'created_at' => date('Y-m-d H:i:s', $timestamp / 1000),
            ];

            $msg = Message::create($messageData);

            Log::info('ProcessInstagramMessage: Message created', [
                'message_id' => $msg->id
            ]);

            // Trigger Bot Response
            try {
                $botService = new \App\Services\BotService();
                $botService->triggerResponse($msg);
            } catch (\Exception $e) {
                Log::error('ProcessInstagramMessage: Bot Trigger Error: ' . $e->getMessage());
            }

            // Update conversation timestamp
            $conversation->touch();

            Log::info('ProcessInstagramMessage: SUCCESS', [
                'channel_id' => $channel->id,
                'conversation_id' => $conversation->id,
                'message_id' => $msg->id
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessInstagramMessage: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
