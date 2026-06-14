<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailAccount;
use App\Jobs\SyncEmailAccountJob;

class SyncEmailAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:sync-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch sync jobs for all eligible email accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accounts = EmailAccount::active()
            ->syncEnabled()
            ->get();
        
        $count = 0;
        foreach ($accounts as $account) {
            // Check if there's already a running or pending job for this account from the last 30 minutes
            // This prevents queue backup if a job gets stuck or takes longer than the schedule interval (5 mins)
            $isSyncing = \App\Models\EmailSyncJob::where('email_account_id', $account->id)
                ->whereIn('status', ['pending', 'running'])
                ->where('created_at', '>', now()->subMinutes(30))
                ->exists();

            if ($account->needsSync() && !$isSyncing) {
                SyncEmailAccountJob::dispatch($account);
                $count++;
            }
        }

        $this->info("Dispatched sync jobs for {$count} accounts.");
    }
}
