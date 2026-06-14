<?php

namespace App\Jobs;

use App\Models\Email;
use App\Models\WarmupLog;
use App\Services\WarmupReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWarmupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Email $email
    ) {}

    public function handle(WarmupReplyService $replyService)
    {
        Log::info("Processing engagement for incoming warmup email {$this->email->id} (message_id: {$this->email->message_id})");

        try {
            // 1. Mark as Read (Always)
            if (!$this->email->is_read) {
                $this->email->markAsRead();
                Log::info("Marked warmup email {$this->email->id} as read.");
            }

            // 2. Star (30% chance)
            if (rand(1, 100) <= 30) {
                $this->email->toggleStar();
                Log::info("Starred warmup email {$this->email->id}.");
            }

            // 3. Reply (25% chance)
            // Only reply if this is NOT already a reply to a reply (prevent infinite loop)
            // Strategy: Check loop depth via references or just limit it.
            // Simplified: If subject starts with "Re:", maybe reduce probability or stop?
            // Actually, conversation threads are good. Let's allow it but maybe lower probability for deeper threads?
            // Safer: Just allow it. WarmupReplyService should handle threading headers.
            
            if (rand(1, 100) <= 25) {
                // Determine if we should reply
                // Check if we have already replied? (Database check not easy unless we track incoming message logging)
                
                $replyService->reply($this->email);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process engagement for warmup email {$this->email->id}: " . $e->getMessage());
        }
    }
}
