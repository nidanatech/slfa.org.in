<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppBaileysContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Channel $channel;
    protected array $contacts;

    public function __construct(Channel $channel, array $contacts)
    {
        $this->channel = $channel;
        $this->contacts = $contacts;
    }

    public function handle(): void
    {
        $serviceUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
        $sessionId = $this->channel->config['session_id'];

        foreach ($this->contacts as $waContact) {
            try {
                $jid = $waContact['id'] ?? null;
                if (!$jid || str_contains($jid, 'broadcast') || str_contains($jid, '@g.us')) {
                    continue; // Skip broadcasts and groups
                }

                $externalId = $jid;
                $phoneNumber = str_contains($jid, '@g.us') ? null : explode('@', $jid)[0];
                $name = $waContact['name'] ?? $waContact['subject'] ?? $waContact['verifiedName'] ?? $waContact['pushName'] ?? ($phoneNumber ?? $externalId);

                $nameParts = Contact::splitName($name);

                $contact = Contact::updateOrCreate(
                    [
                        'channel_id' => $this->channel->id,
                        'external_id' => $externalId
                    ],
                    [
                        'tenant_id' => $this->channel->tenant_id,
                        'name' => $name,
                        'first_name' => $nameParts['first_name'],
                        'last_name' => $nameParts['last_name'],
                        'phones' => $phoneNumber ? [['type' => 'work', 'value' => $phoneNumber]] : [],
                        // Only set meta if new
                        'meta' => [
                            'source' => 'whatsapp_baileys_sync',
                            'wa_name' => $waContact['name'] ?? null,
                            'wa_verified_name' => $waContact['verifiedName'] ?? null,
                        ]
                    ]
                );

                // Try to fetch profile picture if not set
                if ($contact && empty($contact->contact_image)) {
                    \Log::info("Attempting to fetch profile picture for contact {$contact->id} (JID: {$jid}) in SyncWhatsAppBaileysContacts");
                    try {
                        $response = Http::timeout(5)->get("{$serviceUrl}/profile-picture/{$sessionId}/{$jid}");
                        if ($response->successful()) {
                            $data = $response->json();
                            if (!empty($data['url'])) {
                                \Log::info("Fetched profile picture for JID {$jid}: {$data['url']}");
                                $contact->update(['contact_image' => $data['url']]);
                            }
                        } else {
                            \Log::error("Failed to fetch profile picture for JID {$jid}. Status: " . $response->status());
                        }
                    } catch (\Exception $e) {
                         \Log::error("Exception in SyncWhatsAppBaileysContacts profile picture fetch: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("SyncWhatsAppBaileysContacts: Error syncing contact {$waContact['id']}: " . $e->getMessage());
            }
        }
    }
}
