<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMessagingStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $platform;
    protected array $event;

    /**
     * Create a new job instance.
     */
    public function __construct(string $platform, array $event)
    {
        $this->platform = $platform;
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $recipientId = $this->event['sender']['id'] ?? null;
        
        Log::info("ProcessMessagingStatusUpdate: Handling {$this->platform} status update", [
            'recipient_id' => $recipientId,
            'event_types' => array_keys($this->event)
        ]);

        if (isset($this->event['delivery'])) {
            $this->handleDelivery();
        }

        if (isset($this->event['read'])) {
            $this->handleRead();
        }
    }

    protected function handleDelivery()
    {
        $mids = $this->event['delivery']['mids'] ?? [];
        $watermark = $this->event['delivery']['watermark'] ?? null;

        if (!empty($mids)) {
            foreach ($mids as $mid) {
                $this->updateStatus($mid, 'delivered', 'delivered_at');
            }
        } elseif ($watermark) {
            // Update all outbound messages before this watermark
            // (Simpler: just update the specific ones if possible, but watermark is cumulative)
            Log::info("ProcessMessagingStatusUpdate: Watermark delivery received: {$watermark}");
        }
    }

    protected function handleRead()
    {
        $mid = $this->event['read']['mid'] ?? null;
        $watermark = $this->event['read']['watermark'] ?? null;

        if ($mid) {
            $this->updateStatus($mid, 'read', 'seen_at');
        } elseif ($watermark) {
            Log::info("ProcessMessagingStatusUpdate: Watermark read received: {$watermark}");
            // Optional: Update all messages in conversation up to this watermark
        }
    }

    protected function updateStatus(string $externalId, string $status, string $timestampColumn)
    {
        $message = Message::where('meta->message_id', $externalId)
            ->orWhere('meta->external_id', $externalId)
            ->first();

        if ($message && $message->direction === 'outbound') {
            $message->update([
                'status' => $status,
                $timestampColumn => now()
            ]);
            Log::info("ProcessMessagingStatusUpdate: Message {$message->id} updated to {$status}");
        }
    }
}
