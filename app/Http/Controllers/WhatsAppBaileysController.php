<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Services\WhatsAppBaileysService;
use App\Jobs\ProcessWhatsAppBaileysMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppBaileysController extends Controller
{
    protected WhatsAppBaileysService $waService;

    public function __construct(WhatsAppBaileysService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Get status or QR code for connection
     */
    public function getStatus(Request $request)
    {
        $tenant = Auth::user()->tenant;
        
        // Check if there's an existing in-progress connection or create a temporary session ID
        $sessionId = $request->query('session_id') ?? 'wa_' . $tenant->id . '_' . Str::random(8);
        
        // Store sync preferences in cache if provided
        if ($request->has('sync_contacts')) {
            \Illuminate\Support\Facades\Cache::put(
                "wa_sync_prefs_{$sessionId}", 
                [
                    'sync_contacts' => $request->boolean('sync_contacts'),
                    'sync_messages' => $request->boolean('sync_messages')
                ], 
                now()->addMinutes(10)
            );
        }

        $status = $this->waService->getStatus($sessionId);
        
        return response()->json(array_merge($status, ['session_id' => $sessionId]));
    }

    /**
     * Handle webhook from Node.js service
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';
        $sessionId = $payload['sessionId'] ?? '';

        Log::info("WhatsApp Baileys Webhook: [{$event}] for session [{$sessionId}]", [
            'event' => $event,
            'sessionId' => $sessionId,
            'payload_keys' => array_keys($payload)
        ]);

        if ($event === 'message.upsert') {
            ProcessWhatsAppBaileysMessage::dispatch($sessionId, $payload['message']);
        } elseif ($event === 'connection.update') {
            $status = $payload['status'] ?? '';
            
            if ($status === 'connected') {
                $this->finalizeConnection($sessionId);
            }
        } elseif ($event === 'profile.update') {
            $this->updateProfile($sessionId, $payload['profile']);
        } elseif ($event === 'history.sync') {
            $this->handleHistorySync($sessionId, $payload['data']);
        } elseif ($event === 'contacts.upsert') {
            $this->handleContactsSync($sessionId, $payload['contacts']);
        } elseif ($event === 'groups.update') {
            $this->handleGroupsSync($sessionId, $payload['groups']);
        } elseif ($event === 'groups.participants') {
            $this->handleGroupParticipantsSync($sessionId, $payload['update']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle history sync event
     */
    protected function findChannelBySession(string $sessionId)
    {
        $channel = Channel::where('type', 'whatsapp')
            ->where('config->session_id', $sessionId)
            ->first();

        if (!$channel) {
            $parts = explode('_', $sessionId);
            if (count($parts) >= 2) {
                $prefix = "wa_{$parts[1]}_";
                $channel = Channel::where('type', 'whatsapp')
                    ->where('config->session_id', 'like', "{$prefix}%")
                    ->first();
            }
        }

        return $channel;
    }

    /**
     * Handle history sync event
     */
    protected function handleHistorySync(string $sessionId, array $data)
    {
        $channel = $this->findChannelBySession($sessionId);
        if (!$channel) return;

        if ($channel->config['sync_contacts'] ?? true) {
            \App\Jobs\SyncWhatsAppBaileysContacts::dispatch($channel, $data['contacts'] ?? []);
            \App\Jobs\SyncWhatsAppBaileysGroups::dispatch($channel, $data['chats'] ?? []);
        }

        if ($channel->config['sync_messages'] ?? true) {
            \App\Jobs\SyncWhatsAppBaileysHistory::dispatch($channel, $data['messages'] ?? []);
        }
    }

    /**
     * Trigger manual sync for the channel
     */
    public function sync(Request $request, Channel $channel)
    {
        Log::info("Manual WhatsApp sync requested for channel: {$channel->id} (Tenant: {$channel->tenant_id})");

        // Ensure tenant access
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            Log::warning("Unauthorized WhatsApp sync attempt. User tenant: " . Auth::user()->tenant_id . ", Channel tenant: {$channel->tenant_id}");
            abort(403);
        }

        $sessionId = $channel->config['session_id'] ?? null;
        if (!$sessionId) {
            return back()->withErrors(['error' => 'No session ID found for this channel.']);
        }

        try {
            $service = new \App\Services\WhatsAppBaileysService();
            $result = $service->syncAll($sessionId);
            
            if ($result['success'] ?? false) {
                return back()->with('success', "Deep sync triggered. Found {$result['contactsCount']} contacts and {$result['chatsCount']} chats. They will appear shortly.");
            } else {
                return back()->with('error', 'Failed to trigger deep sync: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error("Manual sync trigger failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reach WhatsApp service.']);
        }
    }

    /**
     * Handle contacts upsert event
     */
    protected function handleContactsSync(string $sessionId, array $contacts)
    {
        $channel = $this->findChannelBySession($sessionId);
        if (!$channel) return;

        if ($channel->config['sync_contacts'] ?? true) {
            \App\Jobs\SyncWhatsAppBaileysContacts::dispatch($channel, $contacts);
        }
    }

    /**
     * Handle groups update event
     */
    protected function handleGroupsSync(string $sessionId, array $groups)
    {
        $channel = $this->findChannelBySession($sessionId);
        if (!$channel) return;

        \App\Jobs\SyncWhatsAppBaileysGroups::dispatch($channel, $groups);
    }

    /**
     * Handle group participants update event
     */
    protected function handleGroupParticipantsSync(string $sessionId, array $update)
    {
        $channel = $this->findChannelBySession($sessionId);
        if (!$channel) return;

        // Extract participant update data
        $groupId = $update['id'];
        $participants = $update['participants'] ?? [];
        $action = $update['action'] ?? 'add'; // add, remove, promote, demote

        \App\Jobs\SyncWhatsAppBaileysGroups::dispatch($channel, [
            [
                'id' => $groupId,
                'participantUpdate' => [
                    'action' => $action,
                    'participants' => $participants
                ]
            ]
        ]);
    }

    /**
     * Update profile information for the channel
     */
    protected function updateProfile(string $sessionId, array $profile)
    {
        $channel = Channel::where('type', 'whatsapp')
            ->where('config->session_id', $sessionId)
            ->first();

        if ($channel) {
            $config = $channel->config;
            $config['profile_name'] = $profile['name'] ?? null;
            $config['profile_picture_url'] = $profile['picture'] ?? null;
            $config['whatsapp_id'] = $profile['id'] ?? null;

            $channel->update([
                'name' => 'WhatsApp (' . ($profile['name'] ?? $sessionId) . ')',
                'config' => $config
            ]);
            Log::info("WhatsApp Baileys Profile updated for session {$sessionId}");
        } else {
            Log::warning("WhatsApp Baileys Profile update failed: Channel with session {$sessionId} not found");
        }
    }

    /**
     * Finalize connection when status is 'open'
     */
    protected function finalizeConnection(string $sessionId)
    {
        Log::info("Finalizing WhatsApp connection for session: [{$sessionId}]");
        
        // Extract tenant ID from sessionId (format: wa_TENANTID_RANDOM)
        $parts = explode('_', $sessionId);
        if (count($parts) < 2) {
            Log::error("Failed to finalize session [{$sessionId}]: Invalid session format (tenant ID missing)");
            return;
        }
        
        $tenantId = $parts[1];
        Log::info("Detected Tenant ID: [{$tenantId}] for session [{$sessionId}]");
        
        $status = $this->waService->getStatus($sessionId);
        Log::info("Current session status from Node: ", $status);

        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant) {
            Log::error("Failed to finalize session [{$sessionId}]: Tenant [{$tenantId}] not found");
            return;
        }

        $exists = $tenant->channels()
            ->where('type', 'whatsapp')
            ->where('config->session_id', $sessionId)
            ->exists();

        if (!$exists) {
            $syncPrefs = \Illuminate\Support\Facades\Cache::pull("wa_sync_prefs_{$sessionId}", [
                'sync_contacts' => true,
                'sync_messages' => true
            ]);

            Log::info("Creating new WhatsApp channel for tenant [{$tenantId}]", [
                'sessionId' => $sessionId,
                'sync_contacts' => $syncPrefs['sync_contacts'],
                'sync_messages' => $syncPrefs['sync_messages']
            ]);

            try {
                $channel = $tenant->channels()->create([
                    'name' => 'WhatsApp (' . $sessionId . ')',
                    'type' => 'whatsapp',
                    'config' => [
                        'session_id' => $sessionId,
                        'connected_at' => now()->toDateTimeString(),
                        'sync_contacts' => $syncPrefs['sync_contacts'],
                        'sync_messages' => $syncPrefs['sync_messages'],
                    ],
                    'is_active' => true,
                ]);
                Log::info("WhatsApp Baileys Channel created! ID: {$channel->id}");
            } catch (\Exception $e) {
                Log::error("CRITICAL: Failed to create WhatsApp channel record: " . $e->getMessage(), [
                    'tenantId' => $tenantId,
                    'sessionId' => $sessionId
                ]);
            }
        } else {
            Log::info("WhatsApp channel already exists for session [{$sessionId}]. Skipping creation.");
        }
    }

    public function logout($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        $sessionId = $channel->config['session_id'] ?? null;

        if ($sessionId) {
            $this->waService->logout($sessionId);
        }

        $channel->delete();

        return back()->with('success', 'WhatsApp disconnected.');
    }

    /**
     * Get overview data for the channel
     */
    public function getOverview(\App\Models\Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        
        $service = new \App\Services\WhatsAppOverviewService();
        $data = $service->getOverviewData($channel);

        return response()->json($data);
    }
}
