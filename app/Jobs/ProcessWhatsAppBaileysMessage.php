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

class ProcessWhatsAppBaileysMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sessionId;
    protected array $baileysMessage;

    public function __construct(string $sessionId, array $baileysMessage)
    {
        $this->sessionId = $sessionId;
        $this->baileysMessage = $baileysMessage;
    }

    public function handle(): void
    {
        try {
            // Find channel by sessionId
            $channel = Channel::where('type', 'whatsapp')
                ->where('config->session_id', $this->sessionId)
                ->first();

            // If not found, try by prefix (wa_{tenant_id}_) to handle session rotations
            if (!$channel) {
                $parts = explode('_', $this->sessionId);
                if (count($parts) >= 2) {
                    $prefix = "wa_{$parts[1]}_";
                    $channel = Channel::where('type', 'whatsapp')
                        ->where('config->session_id', 'like', "{$prefix}%")
                        ->first();
                }
            }

            if (!$channel) {
                Log::warning("ProcessWhatsAppBaileysMessage: Channel not found for session {$this->sessionId}");
                return;
            }

            $key = $this->baileysMessage['key'];
            $remoteJid = $key['remoteJid'];
            $fromMe = $key['fromMe'] ?? false;
            $isGroup = str_contains($remoteJid, '@g.us');
            $participant = $key['participant'] ?? ($isGroup ? null : $remoteJid); // For groups, participant is the sender
            
            // If it's from me, the participant is myself (the linked phone)
            // But we still need to know who we are sending TO in 1:1
            $messageId = $key['id'];
            $content = $this->baileysMessage['message']['conversation'] 
                ?? $this->baileysMessage['message']['extendedTextMessage']['text'] 
                ?? $this->baileysMessage['message']['imageMessage']['caption']
                ?? $this->baileysMessage['message']['videoMessage']['caption']
                ?? '';

            // Handle Multimedia
            $mediaType = null;
            $mediaPath = null;
            $messageBody = $this->baileysMessage['message'];
            
            if (isset($messageBody['imageMessage'])) $mediaType = 'image';
            elseif (isset($messageBody['videoMessage'])) $mediaType = 'video';
            elseif (isset($messageBody['audioMessage'])) $mediaType = 'audio';

            if ($mediaType) {
                try {
                    $waServiceUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
                    $response = \Illuminate\Support\Facades\Http::timeout(60)
                        ->post("{$waServiceUrl}/download/{$this->sessionId}", [
                            'message' => $this->baileysMessage
                        ]);

                    if ($response->successful()) {
                        $extension = $mediaType === 'image' ? 'jpg' : ($mediaType === 'video' ? 'mp4' : 'ogg');
                        $filename = "wa_{$this->sessionId}_{$messageId}.{$extension}";
                        $mediaPath = "whatsapp_media/{$filename}";
                        \Illuminate\Support\Facades\Storage::disk('public')->put($mediaPath, $response->body());
                        
                        // If it's audio, transcribe it
                        if ($mediaType === 'audio') {
                            try {
                                $aiService = new \App\Services\AiService();
                                // Create a temporary file for Whisper
                                $tempPath = tempnam(sys_get_temp_dir(), 'wa_audio');
                                file_put_contents($tempPath, $response->body());
                                
                                // Mock an UploadedFile-like object or update transcribe to take path
                                $transcription = $aiService->transcribeFromPath($tempPath);
                                unlink($tempPath);

                                if ($transcription) {
                                    $content = "[Voice Note Transcription]: " . $transcription . ($content ? "\n\n" . $content : "");
                                }
                            } catch (\Exception $e) {
                                Log::error("ProcessWhatsAppBaileysMessage: Transcription Error: " . $e->getMessage());
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("ProcessWhatsAppBaileysMessage: Media Download Error: " . $e->getMessage());
                }
            }

            // Extract sender info
            $senderNumber = $fromMe ? 'me' : explode('@', $participant ?: $remoteJid)[0];
            $pushName = $this->baileysMessage['pushName'] ?? ($isGroup ? "WA User" : ($fromMe ? "Me" : "WA User"));

            // Find or Create contact for the SENDER (unless it's from me)
            $senderContact = null;
            if (!$fromMe && $participant) {
                $senderContact = Contact::where('channel_id', $channel->id)
                    ->where('external_id', $participant)
                    ->first();

                if (!$senderContact) {
                    $nameParts = Contact::splitName($pushName);
                    $senderContact = Contact::create([
                        'channel_id' => $channel->id,
                        'external_id' => $participant,
                        'tenant_id' => $channel->tenant_id,
                        'name' => $pushName,
                        'first_name' => $nameParts['first_name'],
                        'last_name' => $nameParts['last_name'],
                        'phones' => [['type' => 'work', 'value' => $senderNumber]],
                        'meta' => [
                            'source' => 'whatsapp_baileys',
                            'push_name' => $pushName,
                            'jid' => $participant
                        ]
                    ]);
                }

                // Capture profile picture for sender if missing
                if ($senderContact && empty($senderContact->contact_image)) {
                    $this->captureProfilePicture($senderContact, $participant, $waServiceUrl);
                }
            }

            // Find or Create contact for the 1:1 conversation (if not fromMe, it's the sender. if fromMe, it's the recipient)
            $contactJid = $isGroup ? null : $remoteJid;
            $mainContact = null;
            if ($contactJid) {
                $mainContact = Contact::where('channel_id', $channel->id)
                    ->where('external_id', $contactJid)
                    ->first();
                
                if (!$mainContact) {
                    $cPushName = $fromMe ? "Contact" : $pushName;
                    $nameParts = Contact::splitName($cPushName);
                    $mainContact = Contact::create([
                        'channel_id' => $channel->id,
                        'external_id' => $contactJid,
                        'tenant_id' => $channel->tenant_id,
                        'name' => $cPushName,
                        'first_name' => $nameParts['first_name'],
                        'last_name' => $nameParts['last_name'],
                        'phones' => [['type' => 'work', 'value' => explode('@', $contactJid)[0]]],
                    ]);
                }
            }

            // Handle Group specifics
            $group = null;
            if ($isGroup) {
                $group = WhatsAppGroup::where('channel_id', $channel->id)
                    ->where('external_id', $remoteJid)
                    ->first();

                if (!$group) {
                    $group = WhatsAppGroup::create([
                        'tenant_id' => $channel->tenant_id,
                        'channel_id' => $channel->id,
                        'external_id' => $remoteJid,
                        'name' => 'New WhatsApp Group',
                    ]);
                    // Dispatch a sync job to get full group info
                    SyncWhatsAppBaileysGroups::dispatch($channel, [['id' => $remoteJid]]);
                }
            }

            // Find or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'contact_id' => $isGroup ? ($senderContact ? $senderContact->id : ($mainContact ? $mainContact->id : null)) : $mainContact->id,
                    'whatsapp_group_id' => $isGroup ? $group->id : null,
                    'conversation_type' => 'whatsapp_message',
                ],
                [
                    'status' => 'open',
                    'last_message_at' => now(),
                ]
            );

            // Special case: if group conversation was newly created, but contact_id is null, it might need one.
            // But we usually want the 1:1 contact for non-group conversations.
            if ($isGroup && !$conversation->contact_id && $senderContact) {
                $conversation->update(['contact_id' => $senderContact->id]);
            }

            // Check if message already exists
            $exists = Message::where('conversation_id', $conversation->id)
                ->where('meta->message_id', $messageId)
                ->exists();

            if ($exists) return;

            // Create message
            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $content,
                'direction' => $fromMe ? 'outbound' : 'inbound',
                'sender_type' => $fromMe ? 'user' : 'contact',
                'status' => 'read',
                'created_at' => now(),
                'meta' => [
                    'message_id' => $messageId,
                    'type' => 'whatsapp_baileys',
                    'sender_name' => $pushName,
                    'sender_jid' => $participant,
                    'media_type' => $mediaType,
                    'media_path' => $mediaPath,
                    'raw_baileys' => $this->baileysMessage
                ],
            ]);

            // Trigger Bot Response
            if (!$fromMe) {
                try {
                    $botService = new \App\Services\BotService();
                    $botService->triggerResponse($msg);
                } catch (\Exception $e) {
                    Log::error('ProcessWhatsAppBaileysMessage: Bot Trigger Error: ' . $e->getMessage());
                }
            }

            $conversation->touch();

        } catch (\Exception $e) {
            Log::error('ProcessWhatsAppBaileysMessage Failed: ' . $e->getMessage());
        }
    }

    protected function captureProfilePicture(Contact $contact, string $jid, string $serviceUrl)
    {
        \Log::info("Attempting to capture profile picture for contact {$contact->id} (JID: {$jid}) via service: {$serviceUrl}");
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("{$serviceUrl}/profile-picture/{$this->sessionId}/{$jid}");
            
            if ($response->failed()) {
                \Log::error("Profile picture request failed for JID {$jid}. Status: {$response->status()}, Body: {$response->body()}");
                return;
            }

            $data = $response->json();
            if (!empty($data['url'])) {
                \Log::info("Successfully fetched profile picture for JID {$jid}: {$data['url']}");
                $contact->update(['contact_image' => $data['url']]);
            } else {
                \Log::warning("Profile picture request returned no URL for JID {$jid}");
            }
        } catch (\Exception $e) {
            \Log::error("Exception while fetching profile picture for JID {$jid}: " . $e.getMessage());
        }
    }
}
