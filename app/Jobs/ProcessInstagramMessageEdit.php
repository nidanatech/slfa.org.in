<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInstagramMessageEdit implements ShouldQueue
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
        Log::info('ProcessInstagramMessageEdit: Job started', [
            'page_id' => $this->pageId,
            'event' => $this->event,
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
                Log::warning('ProcessInstagramMessageEdit: Channel not found', ['page_id' => $this->pageId]);
                return;
            }

            $editData = $this->event['message_edit'];
            $mid = $editData['mid'];
            $senderId = $this->event['sender']['id'] ?? null;

            // Fetch/Update contact info even in edit check
            if ($senderId) {
                try {
                    $service = new \App\Services\InstagramService($channel);
                    $userData = $service->getUserInfo($senderId);
                    if ($userData) {
                        $profilePic = $userData['profile_picture_url'] ?? null;
                        $displayName = $userData['name'] ?? $userData['username'] ?? 'Instagram User';
                        $nameParts = Contact::splitName($displayName);

                        $contact = Contact::ensureContact($channel, $senderId, [
                            'name' => $displayName,
                            'avatar_url' => $profilePic
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
                            Log::info('ProcessInstagramMessageEdit: Contact updated with better info', [
                                'contact_id' => $contact->id,
                                'updates' => array_keys($updates)
                            ]);
                        }
                        Log::info('ProcessInstagramMessageEdit: Contact updated', ['contact_id' => $contact->id]);
                    }
                } catch (\Exception $e) {
                    Log::warning('ProcessInstagramMessageEdit: Profile update failed');
                }
            }

            // Find the original message
            $message = Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })->where('meta->message_id', $mid)->first();

            if ($message) {
                // Update the message
                $message->update([
                    'content' => $editData['text'] ?? $message->content,
                    'is_edited' => true,
                    'edited_at' => now(),
                    'status' => 'edited',
                    'meta' => array_merge($message->meta ?? [], [
                        'edit_count' => $editData['num_edit'] ?? 1,
                        'last_edit_at' => now()->toDateTimeString(),
                    ])
                ]);

                Log::info('ProcessInstagramMessageEdit: Message updated using new columns', ['message_id' => $message->id]);
            } else {
                Log::warning('ProcessInstagramMessageEdit: Original message not found', ['mid' => $mid]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessInstagramMessageEdit: Failed', [
                'error' => $e->getMessage(),
                'page_id' => $this->pageId
            ]);
        }
    }
}
