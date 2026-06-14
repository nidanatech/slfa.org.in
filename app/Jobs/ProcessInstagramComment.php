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

class ProcessInstagramComment implements ShouldQueue
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
        Log::info('ProcessInstagramComment: Job started', [
            'page_id' => $this->pageId,
            'comment_data_keys' => array_keys($this->commentData)
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
                Log::warning('ProcessInstagramComment: Channel not found');
                return;
            }

            Log::info('ProcessInstagramComment: Channel found', [
                'channel_id' => $channel->id
            ]);

            // Extract comment data
            $commentId = $this->commentData['id'] ?? null;
            $text = $this->commentData['text'] ?? '';
            $mediaId = $this->commentData['media']['id'] ?? $this->commentData['media_id'] ?? null;
            $from = $this->commentData['from'] ?? [];
            $fromId = $from['id'] ?? null;
            $fromUsername = $from['username'] ?? 'unknown';

            if (!$commentId || !$mediaId || !$fromId) {
                Log::error('ProcessInstagramComment: Missing required data', [
                    'comment_id' => $commentId,
                    'media_id' => $mediaId,
                    'from_id' => $fromId
                ]);
                return;
            }

            Log::info('ProcessInstagramComment: Extracted comment data', [
                'comment_id' => $commentId,
                'media_id' => $mediaId,
                'from_username' => $fromUsername
            ]);

            // Try to fetch more detailed user info if possible
            $profilePic = null;
            $displayName = $fromUsername;
            
            try {
                $service = new \App\Services\InstagramService($channel);
                $userData = $service->getUserInfo($fromId);
                if ($userData) {
                    $profilePic = $userData['profile_picture_url'] ?? null;
                    $displayName = $userData['name'] ?? $userData['username'] ?? $fromUsername;
                    Log::info('ProcessInstagramComment: Fetched extended user info', ['data' => $userData]);
                }
            } catch (\Exception $e) {
                Log::warning('ProcessInstagramComment: Extended profile fetch failed', ['error' => $e->getMessage()]);
            }

            $nameParts = Contact::splitName($displayName);

            // Create or update contact
            $contact = Contact::ensureContact($channel, $fromId, [
                'name' => $displayName,
                'avatar_url' => $profilePic,
                'metadata' => [
                    'platform' => 'instagram',
                    'username' => $fromUsername
                ]
            ]);

            // Additional Profile Update Policy: Update placeholder names and avatars if we have better info now
            $updates = [];
            $placeholders = ['Instagram User', 'unknown', 'Instagram Business Account', $fromUsername];
            
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
                Log::info('ProcessInstagramComment: Contact updated with better info', [
                    'contact_id' => $contact->id,
                    'updates' => array_keys($updates)
                ]);
            }

            Log::info('ProcessInstagramComment: Contact found/created', [
                'contact_id' => $contact->id
            ]);

            // Find or create conversation (grouped by media/post)
            $conversation = Conversation::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_id' => $mediaId // Group all comments on same post
                ],
                [
                    'contact_id' => $contact->id,
                    'subject' => 'Instagram Post Comments',
                    'status' => 'open',
                    'metadata' => [
                        'platform' => 'instagram',
                        'type' => 'comment',
                        'media_id' => $mediaId,
                        'post_url' => $this->commentData['media']['permalink'] ?? null
                    ]
                ]
            );

            Log::info('ProcessInstagramComment: Conversation found/created', [
                'conversation_id' => $conversation->id
            ]);

            // Create message for the comment
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'content' => $text,
                'meta' => [
                    'external_id' => $commentId,
                    'platform' => 'instagram',
                    'type' => 'comment',
                    'username' => $fromUsername,
                    'media_id' => $mediaId,
                    'raw_data' => $this->commentData
                ]
            ]);

            Log::info('ProcessInstagramComment: Message created', [
                'message_id' => $message->id
            ]);

            // Update conversation
            $conversation->touch();

            Log::info('ProcessInstagramComment: SUCCESS', [
                'channel_id' => $channel->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessInstagramComment: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
