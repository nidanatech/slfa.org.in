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

class ProcessFacebookMessage implements ShouldQueue
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
        Log::info('ProcessFacebookMessage: Job started', [
            'page_id' => $this->pageId,
            'event_keys' => array_keys($this->event),
            'sender_id' => $this->event['sender']['id'] ?? null,
            'message_id' => $this->event['message']['mid'] ?? null,
        ]);

        try {
            // Find channel by page_id
            $channel = Channel::where('type', 'messenger')
                ->where('config->page_id', $this->pageId)
                ->first();

            if (!$channel) {
                Log::warning('ProcessFacebookMessage: Channel not found', [
                    'page_id' => $this->pageId
                ]);
                return;
            }

            Log::info('ProcessFacebookMessage: Channel found', [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name
            ]);

            $service = new FacebookService($channel);

            // Extract message data
            $sender = $this->event['sender'] ?? [];
            $message = $this->event['message'] ?? [];
            $timestamp = $this->event['timestamp'] ?? time();

            $senderId = $sender['id'] ?? null;
            $messageId = $message['mid'] ?? null;
            $messageText = $message['text'] ?? '';

            if (!$senderId || !$messageId) {
                Log::warning('ProcessFacebookMessage: Missing sender or message ID', [
                    'has_sender' => !empty($senderId),
                    'has_message_id' => !empty($messageId)
                ]);
                return;
            }

            Log::info('ProcessFacebookMessage: Fetching user profile', ['sender_id' => $senderId]);

            // Get user profile
            $userProfile = $service->getUserProfile($senderId);

            Log::info('ProcessFacebookMessage: User profile fetched', [
                'profile' => $userProfile
            ]);

            $userName = $userProfile['name'] ?? 'Facebook User';
            $nameParts = Contact::splitName($userName);

            // Find or Create contact
            $contact = Contact::where('channel_id', $channel->id)
                ->where('external_id', $senderId)
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'channel_id' => $channel->id,
                    'external_id' => $senderId,
                    'tenant_id' => $channel->tenant_id,
                    'name' => $userName,
                    'first_name' => $userProfile['first_name'] ?? $nameParts['first_name'],
                    'last_name' => $userProfile['last_name'] ?? $nameParts['last_name'],
                    'gender' => $userProfile['gender'] ?? null,
                    'time_zone' => $userProfile['timezone'] ?? null,
                    'avatar_url' => $userProfile['profile_pic'] ?? null,
                    'meta' => [
                        'source' => 'facebook_messenger',
                        'profile' => $userProfile,
                        'locale' => $userProfile['locale'] ?? null
                    ]
                ]);
                Log::info('ProcessFacebookMessage: Contact created', ['contact_id' => $contact->id]);
            } else {
                // Standard Update Policy: Update placeholder names and avatars
                $updates = [];
                $placeholders = ['Facebook User', 'unknown'];

                if (in_array($contact->name, $placeholders) && !in_array($userName, $placeholders)) {
                    $updates['name'] = $userName;
                    $updates['first_name'] = $userProfile['first_name'] ?? $nameParts['first_name'];
                    $updates['last_name'] = $userProfile['last_name'] ?? $nameParts['last_name'];
                }

                $profilePic = $userProfile['profile_pic'] ?? null;
                if ($profilePic && $contact->avatar_url !== $profilePic) {
                    $updates['avatar_url'] = $profilePic;
                }

                if (!empty($updates)) {
                    $contact->update($updates);
                    Log::info('ProcessFacebookMessage: Contact updated with better info', [
                        'contact_id' => $contact->id,
                        'updates' => array_keys($updates)
                    ]);
                }
            }

            Log::info('ProcessFacebookMessage: Contact created/found', [
                'contact_id' => $contact->id,
                'contact_name' => $contact->name
            ]);

            // Find or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'contact_id' => $contact->id,
                    'conversation_type' => 'facebook_message',
                ],
                [
                    'status' => 'open',
                    'last_message_at' => now(),
                ]
            );

            Log::info('ProcessFacebookMessage: Conversation created/found', [
                'conversation_id' => $conversation->id
            ]);

            // Track Referral if present
            $referralData = $message['referral'] ?? $this->event['referral'] ?? null;
            if ($referralData && !$conversation->referral()->exists()) {
                try {
                    \App\Models\ConversationReferral::create([
                        'conversation_id' => $conversation->id,
                        'tenant_id' => $channel->tenant_id,
                        'source' => $referralData['source'] ?? null,
                        'type' => $referralData['type'] ?? null,
                        'ref' => $referralData['ref'] ?? null,
                        'ad_id' => $referralData['ad_id'] ?? null,
                        'ad_title' => $referralData['ads_context_data']['ad_title'] ?? null,
                        'post_id' => $referralData['ads_context_data']['post_id'] ?? null,
                        'photo_url' => $referralData['ads_context_data']['photo_url'] ?? null,
                        'metadata' => $referralData,
                    ]);
                    Log::info('ProcessFacebookMessage: Referral data captured', [
                        'conversation_id' => $conversation->id,
                        'source' => $referralData['source'] ?? 'unknown'
                    ]);
                } catch (\Exception $e) {
                    Log::error('ProcessFacebookMessage: Failed to store referral data', [
                        'error' => $e->getMessage(),
                        'conversation_id' => $conversation->id
                    ]);
                }
            }

            // Check if message already exists
            $exists = Message::where('conversation_id', $conversation->id)
                ->where('meta->message_id', $messageId)
                ->exists();

            if ($exists) {
                Log::info('ProcessFacebookMessage: Message already exists, skipping', [
                    'message_id' => $messageId
                ]);
                return; // Skip duplicate
            }

            // Handle attachments
            $attachments = $message['attachments'] ?? [];
            $attachmentData = [];
            
            foreach ($attachments as $attachment) {
                $payload = $attachment['payload'] ?? [];
                $attachmentData[] = [
                    'type' => $attachment['type'] ?? 'unknown',
                    'url' => $payload['url'] ?? null,
                    'sticker_id' => $payload['sticker_id'] ?? null,
                ];
            }

            // Create message
            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $messageText,
                'direction' => 'inbound',
                'sender_type' => 'contact',
                'status' => 'read',
                'created_at' => date('Y-m-d H:i:s', $timestamp / 1000),
                'meta' => [
                    'message_id' => $messageId,
                    'type' => 'facebook_message',
                    'attachments' => $attachmentData,
                    'raw_event' => $this->event
                ],
            ]);

            Log::info('ProcessFacebookMessage: Message created', [
                'message_db_id' => $msg->id,
                'message_id' => $messageId,
                'content_preview' => substr($messageText, 0, 50)
            ]);

            // Trigger Bot Response
            try {
                $botService = new \App\Services\BotService();
                $botService->triggerResponse($msg);
            } catch (\Exception $e) {
                Log::error('ProcessFacebookMessage: Bot Trigger Error: ' . $e->getMessage());
            }

            // Update conversation
            $conversation->touch();

            Log::info('ProcessFacebookMessage: SUCCESS - Message processed completely', [
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
                'contact_name' => $contact->name
            ]);

        } catch (\Exception $e) {
            Log::error('Process Facebook Message Failed', [
                'error' => $e->getMessage(),
                'page_id' => $this->pageId
            ]);
        }
    }
}
