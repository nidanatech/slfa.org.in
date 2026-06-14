<?php

namespace App\Jobs;

use App\Models\GhlConnection;
use App\Services\GhlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGhlJob implements ShouldQueue
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
    public function handle(GhlService $ghlService): void
    {
        Log::info('GoHighLevel Global Sync Job Started');

        $connections = GhlConnection::all();

        foreach ($connections as $connection) {
            try {
                Log::info('Syncing GHL for Tenant: ' . $connection->tenant_id);
                $ghlService->syncPipelines($connection);
                $ghlService->syncContacts($connection);
                $ghlService->syncOpportunities($connection);
                Log::info('GHL sync completed for Tenant: ' . $connection->tenant_id);
            } catch (\Exception $e) {
                Log::error('GHL sync failed for Tenant: ' . $connection->tenant_id . ' - ' . $e->getMessage());
            }
        }

        Log::info('GoHighLevel Global Sync Job Completed');
    }
}
