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

class SimulateWarmupEngagementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $messageId
    ) {}

    public function handle(WarmupReplyService $replyService)
    {
        // In a real peer-to-peer network, this job would run on the RECEIVER'S system.
        // For simulated internal warmup (sending to own controlled accounts), we simulate the actions.
        
        // Find the email in our system (assuming we are sending to another account WE manage)
        // If we sent to an external real email, we cannot 'force' them to reply via this job.
        // BUT, if we synced the inbox of that external account, we can automate the reply FROM there.
        // Currently, we assume we are just marking OUR copy as sent? No.
        
        // Strategy:
        // We can only simplify engagement if we have access to the recipient inbox.
        // If the recipient is ALSO an EmailAccount in our system, we can automate it.
        // If it's external, this job does nothing unless we have an agent there.
        
        // For this implementation, let's assume valid recipients are also connected accounts or accessible via IMAP.
        // OR: This job is just a placeholder for "Expect Engagement".
        
        // USER REQUEST: "SimulateWarmupEngagementJob... app(WarmupReplyService::class)->reply($email)"
        // This implies we CAN reply.
        
        // Let's retry finding the email record. 
        // NOTE: The 'Email' model record is the SENT COPY in our Sent folder.
        // We cannot 'reply' to our own sent email unless we are the recipient.
        
        // If this is a simulation (e.g. self-sending or ghost sending), we might just log it?
        // OR: Maybe the user intends to reply TO the warmup email if we receive one?
        
        // Let's implementing the logic as if we received an email that needs engagement.
        // But the input is `message_id` of the SENT message.
        
        // CRITICAL INTERPRETATION:
        // Automation usually works by:
        // 1. Account A sends to Account B.
        // 2. Account B receives it.
        // 3. Account B replies to Account A.
        
        // If Account B is external, we can't do this.
        // If Account B is managing in our app, we can find the INBOUND copy of this message.
        
        $sentLog = WarmupLog::where('message_id', $this->messageId)->first();
        if (!$sentLog) return;
        
        // Find the matching INBOUND email in our system (if recipient is local)
        // We search by message_id (which usually persists or is referenced)
        $inboundEmail = Email::where('message_id', $this->messageId)
            ->where('direction', 'inbound')
            ->first();

        // If we can't find it, maybe we haven't synced it yet?
        // Or maybe it went to an external email we don't control.
        
        if (!$inboundEmail) {
            // Log that we couldn't engage yet
            return;
        }

        // 1. Mark as Read
        if (!$inboundEmail->is_read) {
            $inboundEmail->markAsRead();
        }

        // 2. Star (30% chance)
        if (rand(1, 100) <= 30) {
            $inboundEmail->toggleStar();
        }

        // 3. Reply (25% chance)
        if (rand(1, 100) <= 25) {
            $replyService->reply($inboundEmail);
        }
    }
}
