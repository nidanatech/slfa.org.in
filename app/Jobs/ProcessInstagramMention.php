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

class ProcessInstagramMention implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $pageId;
    protected array $mentionData;

    public function __construct(string $pageId, array $mentionData)
    {
        $this->pageId = $pageId;
        $this->mentionData = $mentionData;
    }

    public function handle(): void
    {
        Log::info('ProcessInstagramMention: Job started', [
            'page_id' => $this->pageId,
            'mention_data_keys' => array_keys($this->mentionData)
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
                Log::warning('ProcessInstagramMention: Channel not found');
                return;
            }

            Log::info('ProcessInstagramMention: Channel found', [
                'channel_id' => $channel->id
            ]);

            // Extract mention data
            $mentionId = $this->mentionData['id'] ?? null;
            $mediaId = $this->mentionData['media_id'] ?? null;
            $commentId = $this->mentionData['comment_id'] ?? null;

            if (!$mentionId) {
                Log::error('ProcessInstagramMention: Missing mention ID');
                return;
            }

            Log::info('ProcessInstagramMention: Extracted mention data', [
                'mention_id' => $mentionId,
                'media_id' => $mediaId
            ]);

            // For mentions, we might not have detailed user info immediately in the webhook
            // Try to fetch user info using the media owner ID if available
            $profilePic = null;
            $displayName = 'Instagram User';
            $externalId = 'mention_' . $mentionId;

            if ($this->mentionData['media_owner']['id'] ?? null) {
                $ownerId = $this->mentionData['media_owner']['id'];
                $externalId = $ownerId; // Use real user ID if possible
                try {
                    $service = new \App\Services\InstagramService($channel);
                    $userData = $service->getUserInfo($ownerId);
                    if ($userData) {
                        $profilePic = $userData['profile_picture_url'] ?? null;
                        $displayName = $userData['name'] ?? $userData['username'] ?? 'Instagram User';
                        Log::info('ProcessInstagramMention: Fetched user info', ['data' => $userData]);
                    }
                } catch (\Exception $e) {
                    Log::warning('ProcessInstagramMention: Profile fetch failed');
                }
            }

            $nameParts = Contact::splitName($displayName);

            // Create or update contact
            $contact = Contact::ensureContact($channel, $externalId, [
                'name' => $displayName,
                'avatar_url' => $profilePic,
                'metadata' => [
                    'platform' => 'instagram',
                    'type' => 'mention'
                ]
            ]);

            // Additional Profile Update Policy: Update placeholder names and avatars if we have better info now
            $updates = [];
            $placeholders = ['unknown', 'Instagram User', 'Instagram Business Account'];

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
                Log::info('ProcessInstagramMention: Contact updated', ['contact_id' => $contact->id]);
            }



            Log::info('ProcessInstagramMention: Contact found/created', [
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
                    'subject' => 'Instagram Story Mention',
                    'status' => 'open',
                    'metadata' => [
                        'platform' => 'instagram',
                        'type' => 'mention',
                        'media_id' => $mediaId,
                        'comment_id' => $commentId
                    ]
                ]
            );

            Log::info('ProcessInstagramMention: Conversation found/created', [
                'conversation_id' => $conversation->id
            ]);

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'content' => 'You were mentioned in an Instagram story',
                'meta' => [
                    'external_id' => $mentionId,
                    'platform' => 'instagram',
                    'type' => 'mention',
                    'media_id' => $mediaId,
                    'raw_data' => $this->mentionData
                ]
            ]);

            Log::info('ProcessInstagramMention: Message created', [
                'message_id' => $message->id
            ]);

            // Update conversation
            $conversation->touch();

            Log::info('ProcessInstagramMention: SUCCESS', [
                'channel_id' => $channel->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessInstagramMention: Exception occurred', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
