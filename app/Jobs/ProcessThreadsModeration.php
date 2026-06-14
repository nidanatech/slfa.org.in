<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessThreadsModeration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $threadsUserId;
    protected array $moderationData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $threadsUserId, array $moderationData)
    {
        $this->threadsUserId = $threadsUserId;
        $this->moderationData = $moderationData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessThreadsModeration: Job started', [
            'threads_user_id' => $this->threadsUserId,
            'moderation_data' => $this->moderationData
        ]);

        try {
            // Find the channel
            $channel = Channel::where('type', 'threads')
                ->where('config->user_id', $this->threadsUserId)
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::warning('ProcessThreadsModeration: Channel not found');
                return;
            }

            $replyId = $this->moderationData['id'] ?? null;
            $hideStatus = $this->moderationData['hide_status'] ?? null;

            if (!$replyId) {
                Log::error('ProcessThreadsModeration: Missing reply ID');
                return;
            }

            // Find the message by external ID
            $message = Message::whereHas('conversation', function ($query) use ($channel) {
                $query->where('channel_id', $channel->id);
            })
            ->where('external_id', $replyId)
            ->first();

            if ($message) {
                // Update message metadata with moderation status
                $metadata = $message->metadata ?? [];
                $metadata['hide_status'] = $hideStatus;
                $metadata['moderated_at'] = now()->toIso8601String();

                $message->update(['metadata' => $metadata]);

                Log::info('ProcessThreadsModeration: Message updated', [
                    'message_id' => $message->id,
                    'hide_status' => $hideStatus
                ]);
            } else {
                Log::warning('ProcessThreadsModeration: Message not found', [
                    'reply_id' => $replyId
                ]);
            }

            // TODO: Send notification to team about moderation event
            // You could dispatch a notification job here

        } catch (\Exception $e) {
            Log::error('ProcessThreadsModeration: Exception occurred', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
