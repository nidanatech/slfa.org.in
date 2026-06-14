<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Services\EmailWarmupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmailWarmupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    protected $emailAccount;

    public function __construct(EmailAccount $emailAccount)
    {
        $this->emailAccount = $emailAccount;
    }

    public function handle(EmailWarmupService $warmupService)
    {
        if (!$this->emailAccount->warmup_enabled) {
            Log::info("Warmup not enabled for account: {$this->emailAccount->email_address}");
            return;
        }

        if ($this->emailAccount->status !== 'active') {
            Log::warning("Cannot process warmup for inactive account: {$this->emailAccount->email_address}");
            return;
        }

        try {
            $sentCount = $warmupService->processDailyWarmup($this->emailAccount);

            Log::info("Processed warmup for {$this->emailAccount->email_address}: {$sentCount} emails sent");

        } catch (\Exception $e) {
            Log::error("Warmup processing failed for {$this->emailAccount->email_address}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Warmup job failed permanently for {$this->emailAccount->email_address}: " . $exception->getMessage());
    }
}
