<?php

namespace App\Services;

use App\Jobs\DispatchWarmupBatchJob;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Log;

/**
 * Email Warmup Orchestrator (Refactored v2)
 *
 * Responsibility:
 * 1. Identify accounts needing warmup
 * 2. Calculate daily quotas
 * 3. Check safety guards
 * 4. Dispatch batch jobs (no direct sending)
 */
class EmailWarmupService
{
    // Warmup schedule (day => ['send' => quota, 'reply_rate' => %])
    protected $warmupSchedule = [
        1  => ['send' => 5,  'reply_rate' => 20],
        2  => ['send' => 8,  'reply_rate' => 20],
        3  => ['send' => 12, 'reply_rate' => 25],
        5  => ['send' => 20, 'reply_rate' => 25],
        7  => ['send' => 30, 'reply_rate' => 30],
        14 => ['send' => 50, 'reply_rate' => 35],
        21 => ['send' => 75, 'reply_rate' => 40],
        28 => ['send' => 100,'reply_rate' => 45],
        35 => ['send' => 150,'reply_rate' => 50],
        42 => ['send' => 200,'reply_rate' => 55],
    ];

    /**
     * Entry Point: Run Daily Warmup for ALL Accounts
     * Should be called by Scheduler
     */
    public function runDailyWarmup()
    {
        Log::info("Starting Daily Warmup Cycle");

        EmailAccount::where('warmup_enabled', true)
            ->whereNull('warmup_completed_at')
            ->chunk(50, function ($accounts) {
                foreach ($accounts as $account) {
                    $this->processAccount($account);
                }
            });
            
        Log::info("Daily Warmup Cycle Dispatch Complete");
    }

    protected function processAccount(EmailAccount $account)
    {
        // 1. Check Safety Guards
        if ($this->shouldPauseWarmup($account)) {
            Log::info("Warmup Paused for {$account->email_address} due to safety guards.");
            return;
        }

        // 2. Adjust Warmup Day (Ramp Up/Down)
        $this->adjustWarmupDay($account);

        // 3. Get Quota
        $day = $account->warmup_day ?? 1;
        $quota = $this->getQuotaForDay($day);

        if ($quota > 0) {
            // 4. Dispatch Batch Job
            DispatchWarmupBatchJob::dispatch(
                $account->id,
                $quota
            );
            
            // Update last run time
            $account->update(['last_warmup_at' => now()]);
        }
    }

    protected function shouldPauseWarmup(EmailAccount $account): bool
    {
        // Guard 1: High Bounce Rate (>3%)
        if ($account->bounce_rate > 3) return true;

        // Guard 2: Spam Reports (>1)
        if ($account->spam_reports > 1) return true;

        // Guard 3: Provider Error Rate (>5%)
        if ($account->provider_error_rate > 5) return true;

        // Guard 4: Weekends (for initial warmup phases)
        // Rule: Never warmup on weekends if day < 14
        if (now()->isWeekend() && ($account->warmup_day < 14)) {
            return true;
        }

        return false;
    }

    protected function adjustWarmupDay(EmailAccount $account)
    {
        // Logic: If reputation is good, increment day. If bad, decrement or stay.
        // For simplicity: If inbox rate > 95% AND reply rate > 20%, go up.
        // Otherwise stay or go down.
        
        // Initial phase: just increment if we successfully ran yesterday
        // Implementation: We increment if last_warmup_at was yesterday (successful run)
        // Real implementation should be smarter based on `warmup_logs` feedback.
        
        // Use user provided logic:
        // if ($account->inbox_rate > 95 && $account->reply_rate > 20)
        
        if ($account->inbox_rate > 95 && $account->reply_rate >= 20) {
            $account->increment('warmup_day');
        } else {
            // If struggling, maybe step back
            if ($account->daily_limit_reached_errors > 0 || $account->provider_error_rate > 5) {
                // If day > 1, decrement
                 if ($account->warmup_day > 1) {
                     $account->decrement('warmup_day');
                 }
            }
            // else stay same
        }
        
        // Cap day at 42 (completed)
        if ($account->warmup_day >= 42) {
             $this->completeWarmup($account);
        }
    }

    protected function getQuotaForDay($day)
    {
        // Find exact or closest lower tier
        $quota = 5;
        foreach ($this->warmupSchedule as $level => $config) {
            if ($day >= $level) {
                $quota = $config['send'];
            }
        }
        return $quota;
    }

    public function startWarmup(EmailAccount $emailAccount, $autoStart = false)
    {
        if (!$autoStart) {
            $emailAccount->update(['warmup_available' => true]);
            return false;
        }

        $emailAccount->update([
            'warmup_started_at' => now(),
            'warmup_enabled' => true,
            'warmup_day' => 1,
            'last_warmup_at' => null, // Reset
        ]);

        return true;
    }

    public function enableWarmup(EmailAccount $emailAccount)
    {
        return $this->startWarmup($emailAccount, true);
    }

    public function completeWarmup(EmailAccount $emailAccount)
    {
        $emailAccount->update([
            'warmup_enabled' => false,
            'warmup_completed_at' => now(),
        ]);
        Log::info("Completed email warmup for account: {$emailAccount->email_address}");
    }

    public function skipWarmup(EmailAccount $emailAccount)
    {
        $emailAccount->update([
            'warmup_enabled' => false,
            'warmup_skipped' => true,
            'warmup_skipped_at' => now(),
        ]);
        Log::info("Skipped email warmup for account: {$emailAccount->email_address}");
    }
    
    // Legacy support / Getter methods
    public function getWarmupStats(EmailAccount $emailAccount)
    {
        // Return existing stats structure for frontend compatibility
        $day = $emailAccount->warmup_day ?? 1;
        $totalDays = 42;
        $progress = min(100, ($day / $totalDays) * 100);

        return [
            'status' => $emailAccount->warmup_enabled ? 'in_progress' : ($emailAccount->warmup_completed_at ? 'completed' : 'not_started'),
            'current_day' => $day,
            'total_days' => $totalDays,
            'progress' => round($progress, 2),
            'emails_sent' => $emailAccount->warmup_emails_sent ?? 0,
            'emails_today' => $this->getQuotaForDay($day),
            'started_at' => $emailAccount->warmup_started_at,
            'completed_at' => $emailAccount->warmup_completed_at,
        ];
    }
    public function isWarmupRecommended(EmailAccount $emailAccount)
    {
        // Internal Isolation Policy:
        // We only recommend warmup if there is at least 1 other active account 
        // in the tenant to send emails TO.
        
        $otherActiveAccounts = EmailAccount::where('tenant_id', $emailAccount->tenant_id)
            ->where('id', '!=', $emailAccount->id)
            ->where('status', 'active')
            ->exists();

        return $otherActiveAccounts;
    }
}
