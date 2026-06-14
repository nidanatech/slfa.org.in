<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsAppGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppBaileysHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Channel $channel;
    protected array $messages;

    public function __construct(Channel $channel, array $messages)
    {
        $this->channel = $channel;
        $this->messages = $messages;
    }

    public function handle(): void
    {
        foreach ($this->messages as $waMsg) {
            try {
                $key = $waMsg['key'] ?? null;
                if (!$key || empty($key['remoteJid'])) continue;

                $remoteJid = $key['remoteJid'];
                $isGroup = str_contains($remoteJid, '@g.us');
                $fromMe = $key['fromMe'] ?? false;
                $participant = $key['participant'] ?? ($isGroup ? null : $remoteJid);
                $messageId = $key['id'];
                // Extract content
                $content = $waMsg['message']['conversation'] 
                    ?? $waMsg['message']['extendedTextMessage']['text'] 
                    ?? $waMsg['message']['imageMessage']['caption']
                    ?? '';

                // Find/Create Contact for the 1:1 conversation or sender
                $senderNumber = $fromMe ? 'me' : explode('@', $participant ?: $remoteJid)[0];
                $contactJid = $isGroup ? $participant : $remoteJid;
                
                // CRITICAL: Ensure we don't treat the group JID itself as a contact
                if ($contactJid && str_contains($contactJid, '@g.us')) {
                    $contactJid = null;
                }

                $contact = null;
                if ($contactJid) {
                    $contact = Contact::where('channel_id', $this->channel->id)
                        ->where('external_id', $contactJid)
                        ->first();

                    if (!$contact) {
                        $pushName = $waMsg['pushName'] ?? ($fromMe ? "Me" : "WA User");
                        $nameParts = Contact::splitName($pushName);
                        
                        $contact = Contact::create([
                            'tenant_id' => $this->channel->tenant_id,
                            'channel_id' => $this->channel->id,
                            'external_id' => $contactJid,
                            'name' => $pushName,
                            'first_name' => $nameParts['first_name'],
                            'last_name' => $nameParts['last_name'],
                            'phones' => [['type' => 'work', 'value' => explode('@', $contactJid)[0]]],
                            'meta' => [
                                'source' => 'whatsapp_baileys_history_sync',
                                'push_name' => $pushName,
                                'jid' => $contactJid
                            ]
                        ]);
                    }

                    // Try to fetch profile picture if missing
                    if ($contact && empty($contact->contact_image)) {
                        $serviceUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
                        $sessionId = $this->channel->config['session_id'];
                        \Log::info("History Sync: Attempting to fetch profile picture for contact {$contact->id} (JID: {$contactJid})");
                        try {
                            $response = Http::timeout(5)->get("{$serviceUrl}/profile-picture/{$sessionId}/{$contactJid}");
                            if ($response->successful()) {
                                $data = $response->json();
                                if (!empty($data['url'])) {
                                    \Log::info("History Sync: Successfully fetched profile picture for JID {$contactJid}: {$data['url']}");
                                    $contact->update(['contact_image' => $data['url']]);
                                } else {
                                    \Log::warning("History Sync: No profile picture URL returned for JID {$contactJid}");
                                }
                            } else {
                                \Log::error("History Sync: Failed to fetch profile picture for JID {$contactJid}. Status: " . $response->status());
                            }
                        } catch (\Exception $e) { 
                            \Log::error("History Sync: Exception while fetching profile picture for JID {$contactJid}: " . $e->getMessage());
                        }
                    }
                }

                // Handle Group specifics
                $group = null;
                if ($isGroup) {
                    $group = WhatsAppGroup::where('channel_id', $this->channel->id)
                        ->where('external_id', $remoteJid)
                        ->first();

                    if (!$group) {
                        $group = WhatsAppGroup::create([
                            'tenant_id' => $this->channel->tenant_id,
                            'channel_id' => $this->channel->id,
                            'external_id' => $remoteJid,
                            'name' => 'Synced Group',
                        ]);
                        // Only sync full metadata if it's very recent or we are brave
                        // SyncWhatsAppBaileysGroups::dispatch($this->channel, [['id' => $remoteJid]]);
                    }
                }

                // Find/Create Conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'tenant_id' => $this->channel->tenant_id,
                        'channel_id' => $this->channel->id,
                        'contact_id' => $isGroup ? ($contact ? $contact->id : ($group ? null : null)) : ($contact ? $contact->id : null),
                        'whatsapp_group_id' => $isGroup ? ($group ? $group->id : null) : null,
                        'conversation_type' => 'whatsapp_message',
                    ],
                    [
                        'status' => 'open',
                        'last_message_at' => now(),
                    ]
                );

                if ($isGroup && !$conversation->contact_id && $contact) {
                    $conversation->update(['contact_id' => $contact->id]);
                }

                // Check if message exists
                $exists = Message::where('conversation_id', $conversation->id)
                    ->where('meta->message_id', $messageId)
                    ->exists();

                if ($exists) continue;

                if ($contact && !$contact->sync_enabled) {
                    continue; // Skip muted contacts
                }

                // Handle Multimedia
                $mediaType = null;
                $mediaPath = null;
                $messageBody = $waMsg['message'] ?? [];
                $messageContent = $content;

                if (isset($messageBody['imageMessage'])) $mediaType = 'image';
                elseif (isset($messageBody['videoMessage'])) $mediaType = 'video';
                elseif (isset($messageBody['audioMessage'])) $mediaType = 'audio';

                if ($mediaType) {
                    try {
                        $waServiceUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
                        $response = \Illuminate\Support\Facades\Http::timeout(60)
                            ->post("{$waServiceUrl}/download/{$this->channel->config['session_id']}", [
                                'message' => $waMsg
                            ]);

                        if ($response->successful()) {
                            $extension = $mediaType === 'image' ? 'jpg' : ($mediaType === 'video' ? 'mp4' : 'ogg');
                            $filename = "wa_{$this->channel->config['session_id']}_{$messageId}.{$extension}";
                            $mediaPath = "whatsapp_media/{$filename}";
                            \Illuminate\Support\Facades\Storage::disk('public')->put($mediaPath, $response->body());
                            
                            // Transcribe audio (optional: only if recent or small number)
                            if ($mediaType === 'audio') {
                                $aiService = new \App\Services\AiService();
                                $tempPath = tempnam(sys_get_temp_dir(), 'wa_audio_sync');
                                file_put_contents($tempPath, $response->body());
                                
                                $transcription = $aiService->transcribeFromPath($tempPath);
                                unlink($tempPath);

                                if ($transcription) {
                                    $messageContent = "[Voice Note Transcription]: " . $transcription . ($messageContent ? "\n\n" . $messageContent : "");
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("SyncWhatsAppBaileysHistory: Media Error: " . $e->getMessage());
                    }
                }

                // Create message
                Message::updateOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'meta->message_id' => $messageId
                    ],
                    [
                        'content' => $messageContent,
                        'direction' => $fromMe ? 'outbound' : 'inbound',
                        'sender_type' => $fromMe ? 'user' : 'contact',
                        'status' => 'read',
                        'created_at' => isset($waMsg['messageTimestamp']) ? date('Y-m-d H:i:s', $waMsg['messageTimestamp']) : now(),
                        'meta' => [
                            'message_id' => $messageId,
                            'type' => 'whatsapp_baileys_history',
                            'sender_name' => $waMsg['pushName'] ?? null,
                            'sender_jid' => $participant,
                            'media_type' => $mediaType,
                            'media_path' => $mediaPath,
                            'raw_message' => $waMsg,
                        ],
                    ]
                );

            } catch (\Exception $e) {
                Log::error("SyncWhatsAppBaileysHistory: Error syncing message: " . $e->getMessage());
            }
        }
    }
}
