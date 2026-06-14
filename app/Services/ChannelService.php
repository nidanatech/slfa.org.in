<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelConnectionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ChannelService
{
    /**
     * Register a channel connection, restoring if it exists.
     *
     * @param int $tenantId
     * @param string $type
     * @param string $name
     * @param array $config Must contain the unique identifier key used for matching
     * @param string $identifierKey Key in the config array that is unique (e.g. 'page_id', 'email')
     * @return Channel
     */
    public function registerConnection(int $tenantId, string $type, string $name, array $config, string $identifierKey)
    {
        return DB::transaction(function () use ($tenantId, $type, $name, $config, $identifierKey) {
            
            $identifierValue = $config[$identifierKey] ?? null;

            if (!$identifierValue) {
                throw new \Exception("Identifier key '{$identifierKey}' missing from config.");
            }

            // Global Check: Prevent duplicate connection by other tenants
            // Note: This query implicitly excludes soft-deleted records. 
            // So if a channel is trashed in another tenant, it CAN be connected here.
            $ownedByOther = Channel::where('type', $type)
                ->where('config->' . $identifierKey, (string)$identifierValue)
                ->where('tenant_id', '!=', $tenantId)
                ->exists();

            if ($ownedByOther) {
                throw new \Exception("This channel is already connected to another business.");
            }

            // Find existing (including soft deleted) within THIS tenant
            $existing = Channel::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                // Use JSON extraction for exact match
                ->where('config->' . $identifierKey, (string)$identifierValue)
                ->first();

            if ($existing) {
                $wasDeleted = $existing->trashed();
                
                // Restore if deleted
                if ($wasDeleted) {
                    $existing->restore();
                }

                // Update config and status
                $existing->update([
                    'name' => $name,
                    'config' => $config,
                    'is_active' => true,
                    'deleted_at' => null // Ensure it's not deleted
                ]);
                
                $channel = $existing;
                $event = $wasDeleted ? 'reconnected' : 'updated';
            } else {
                // Create new
                $channel = Channel::create([
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'name' => $name,
                    'config' => $config,
                    'is_active' => true,
                ]);
                $event = 'connected';
            }

            // Log the event
            $this->logEvent($channel, $event);

            return $channel;
        });
    }

    /**
     * Mark channel as disconnected (soft delete?) or just inactive depending on logic.
     * Usually disconnect means soft delete so we can restore later.
     */
    public function disconnect(Channel $channel)
    {
        $channel->update(['is_active' => false]);
        $channel->delete(); // Soft delete
        
        $this->logEvent($channel, 'disconnected');
    }

    protected function logEvent(Channel $channel, string $event)
    {
        ChannelConnectionLog::create([
            'channel_id' => $channel->id,
            'user_id' => auth()->id(),
            'event_type' => $event,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
