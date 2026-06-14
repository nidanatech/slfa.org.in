<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppBaileysGroups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Channel $channel;
    protected array $groups;

    public function __construct(Channel $channel, array $groups)
    {
        $this->channel = $channel;
        $this->groups = $groups;
    }

    public function handle(): void
    {
        $serviceUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
        $sessionId = $this->channel->config['session_id'];

        foreach ($this->groups as $waGroup) {
            try {
                $jid = $waGroup['id'] ?? null;
                if (!$jid || !str_contains($jid, '@g.us')) {
                    continue; // Skip non-groups
                }

                $group = WhatsAppGroup::updateOrCreate(
                    [
                        'tenant_id' => $this->channel->tenant_id,
                        'channel_id' => $this->channel->id,
                        'external_id' => $jid,
                    ],
                    [
                        'name' => $waGroup['subject'] ?? $waGroup['name'] ?? 'WhatsApp Group',
                        'description' => $waGroup['desc'] ?? null,
                    ]
                );

                // If it's a participant update event
                if (isset($waGroup['participantUpdate'])) {
                    $this->handleParticipantUpdate($group, $waGroup['participantUpdate']);
                } else {
                    // Fetch full metadata if it's a new or skeletal group
                    $this->syncFullGroupData($group, $serviceUrl, $sessionId);
                }

                // Fetch group profile picture
                $this->syncProfilePicture($group, $serviceUrl, $sessionId);

            } catch (\Exception $e) {
                Log::error("SyncWhatsAppBaileysGroups Error for {$waGroup['id']}: " . $e->getMessage());
            }
        }
    }

    protected function syncFullGroupData(WhatsAppGroup $group, string $serviceUrl, string $sessionId)
    {
        try {
            $response = Http::get("{$serviceUrl}/group-metadata/{$sessionId}/{$group->external_id}");
            if ($response->successful()) {
                $metadata = $response->json();
                
                $group->update([
                    'name' => $metadata['subject'] ?? $group->name,
                    'description' => $metadata['desc'] ?? $group->description,
                    'metadata' => $metadata,
                ]);

                // Sync participants
                if (!empty($metadata['participants'])) {
                    $this->syncParticipants($group, $metadata['participants']);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch full metadata for group {$group->external_id}: " . $e->getMessage());
        }
    }

    protected function syncParticipants(WhatsAppGroup $group, array $participants)
    {
        $contactIds = [];

        foreach ($participants as $p) {
            $pjid = $p['id'];
            $phone = explode('@', $pjid)[0];
            
            $contact = Contact::where('channel_id', $group->channel_id)
                ->where('external_id', $pjid)
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'tenant_id' => $group->tenant_id,
                    'channel_id' => $group->channel_id,
                    'external_id' => $pjid,
                    'name' => $phone,
                    'phones' => [['type' => 'work', 'value' => $phone]],
                ]);

                // Fetch participant profile picture
                try {
                    $res = Http::timeout(5)->get("{$serviceUrl}/profile-picture/{$sessionId}/{$pjid}");
                    if ($res->successful() && !empty($res->json('url'))) {
                        $contact->update(['contact_image' => $res->json('url')]);
                    }
                } catch (\Exception $e) {}
            }

            WhatsAppGroupParticipant::updateOrCreate(
                [
                    'whatsapp_group_id' => $group->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'role' => $p['admin'] ? ($p['admin'] === 'superadmin' ? 'creator' : 'admin') : 'member',
                ]
            );

            $contactIds[] = $contact->id;
        }

        // Remove participants no longer in the list (if doing full sync)
        WhatsAppGroupParticipant::where('whatsapp_group_id', $group->id)
            ->whereNotIn('contact_id', $contactIds)
            ->delete();
    }

    protected function handleParticipantUpdate(WhatsAppGroup $group, array $update)
    {
        $action = $update['action'];
        $participants = $update['participants'];

        foreach ($participants as $pjid) {
            $phone = explode('@', $pjid)[0];

            $contact = Contact::where('channel_id', $group->channel_id)
                ->where('external_id', $pjid)
                ->first();

            if (!$contact && $action !== 'remove') {
                $contact = Contact::create([
                    'tenant_id' => $group->tenant_id,
                    'channel_id' => $group->channel_id,
                    'external_id' => $pjid,
                    'name' => $phone,
                    'phones' => [['type' => 'work', 'value' => $phone]],
                ]);

                // Fetch participant profile picture
                try {
                    $res = Http::timeout(5)->get("{$serviceUrl}/profile-picture/{$sessionId}/{$pjid}");
                    if ($res->successful() && !empty($res->json('url'))) {
                        $contact->update(['contact_image' => $res->json('url')]);
                    }
                } catch (\Exception $e) {}
            }

            if (!$contact) continue;

            if ($action === 'add') {
                WhatsAppGroupParticipant::updateOrCreate(
                    ['whatsapp_group_id' => $group->id, 'contact_id' => $contact->id],
                    ['role' => 'member']
                );
            } elseif ($action === 'remove') {
                WhatsAppGroupParticipant::where('whatsapp_group_id', $group->id)
                    ->where('contact_id', $contact->id)
                    ->delete();
            } elseif ($action === 'promote') {
                WhatsAppGroupParticipant::where('whatsapp_group_id', $group->id)
                    ->where('contact_id', $contact->id)
                    ->update(['role' => 'admin']);
            } elseif ($action === 'demote') {
                WhatsAppGroupParticipant::where('whatsapp_group_id', $group->id)
                    ->where('contact_id', $contact->id)
                    ->update(['role' => 'member']);
            }
        }
    }

    protected function syncProfilePicture(WhatsAppGroup $group, string $serviceUrl, string $sessionId)
    {
        try {
            $response = Http::get("{$serviceUrl}/profile-picture/{$sessionId}/{$group->external_id}");
            $data = $response->json();
            if (!empty($data['url'])) {
                $group->update(['avatar_url' => $data['url']]);
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
