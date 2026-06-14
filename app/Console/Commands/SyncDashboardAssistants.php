<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\DashboardAssistantService;

class SyncDashboardAssistants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assistants:sync {tenant_id? : Optional tenant ID to sync specific tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize dashboard assistant instructions and tools with OpenAI';

    /**
     * Execute the console command.
     */
    public function handle(DashboardAssistantService $service)
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return 1;
            }
            $tenants = collect([$tenant]);
        } else {
            $tenants = Tenant::whereNotNull('dashboard_assistant_id')->get();
            $this->info("Found " . $tenants->count() . " tenants with dashboard assistants.");
        }

        if ($tenants->isEmpty()) {
            $this->info("No assistants to sync.");
            return 0;
        }

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $service->syncAssistant($tenant);
            } catch (\Exception $e) {
                $this->error("\nFailed to sync tenant #{$tenant->id} ({$tenant->name}): " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nSynchronization completed.");

        return 0;
    }
}
