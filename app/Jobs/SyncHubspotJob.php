<?php

namespace App\Jobs;

use App\Models\HubspotConnection;
use App\Services\HubspotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncHubspotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(HubspotService $hubspotService): void
    {
        Log::info('HubSpot Global Sync Job Started');

        $connections = HubspotConnection::all();

        foreach ($connections as $connection) {
            try {
                Log::info('Syncing HubSpot for Tenant: ' . $connection->tenant_id);
                $hubspotService->syncContacts($connection);
                $hubspotService->syncDeals($connection);
                Log::info('HubSpot sync completed for Tenant: ' . $connection->tenant_id);
            } catch (\Exception $e) {
                Log::error('HubSpot sync failed for Tenant: ' . $connection->tenant_id . ' - ' . $e->getMessage());
            }
        }

        Log::info('HubSpot Global Sync Job Completed');
    }
}
