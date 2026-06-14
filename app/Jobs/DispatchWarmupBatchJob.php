<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchWarmupBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $emailAccountId,
        public int $quota
    ) {}

    public function handle()
    {
        $account = EmailAccount::findOrFail($this->emailAccountId);

        // Double check if pause needed (race condition check)
        if ($this->shouldPause($account)) {
            Log::info("DispatchWarmupBatchJob: Pausing warmup for {$account->email_address} due to safety guards.");
            return;
        }

        // Get recipients (Internal Pool Only)
        $recipients = $this->getRecipients($account, $this->quota);

        if (empty($recipients)) {
            Log::warning("DispatchWarmupBatchJob: No recipients found for {$account->email_address}");
            return;
        }

        Log::info("Dispatching {$this->quota} warmup emails for {$account->email_address}");

        foreach ($recipients as $index => $recipient) {
            // Random delay between 5 to 8 minutes * index
            // This spreads them out over the day naturally
            $delayMinutes = rand(5, 8) * ($index + 1);
            
            SendWarmupEmailJob::dispatch(
                $account->id,
                $recipient
            )->delay(now()->addMinutes($delayMinutes));
        }
    }

    protected function shouldPause(EmailAccount $account): bool
    {
        if ($account->bounce_rate > 3) return true;
        if ($account->spam_reports > 1) return true;
        if ($account->provider_error_rate > 5) return true;
        if (now()->isWeekend()) return true;

        return false;
    }

    protected function getRecipients(EmailAccount $sender, int $count)
    {
        // Internal Isolation Strategy:
        // Only fetch other email accounts BELONGING TO THE SAME TENANT.
        // This ensures privacy and control.
        
        $recipients = EmailAccount::where('tenant_id', $sender->tenant_id)
            ->where('id', '!=', $sender->id) // Not self
            ->where('warmup_enabled', true) // Only send to those opted into the "pool"
            ->inRandomOrder()
            ->limit($count)
            ->pluck('email_address')
            ->toArray();
        
        return $recipients;
    }
}
