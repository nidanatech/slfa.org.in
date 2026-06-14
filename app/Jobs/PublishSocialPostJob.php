<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\SocialMediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    public function __construct(SocialPost $post)
    {
        $this->post = $post;
    }

    public function handle(SocialMediaService $service): void
    {
        $this->post->update(['status' => 'publishing']);

        $postChannels = $this->post->postChannels;
        
        foreach ($postChannels as $postChannel) {
            try {
                $channel = $postChannel->channel;
                $media = $this->post->media->map(fn($m) => $m->full_path)->toArray();
                
                $result = match($channel->type) {
                    'facebook' => $service->publishToFacebook($channel, $this->post, $media),
                    'instagram' => $service->publishToInstagram($channel, $this->post, $media),
                    'linkedin' => $service->publishToLinkedIn($channel, $this->post, $media),
                    'twitter' => $service->publishToTwitter($channel, $this->post, $media),
                    'tiktok' => $service->publishToTikTok($channel, $this->post, $media),
                    'google_business' => $service->publishToGoogleBusiness($channel, $this->post, $media),
                    default => throw new \Exception("Unsupported platform: {$channel->type}"),
                };

                $postChannel->markAsPublished(
                    $result['id'] ?? 'unknown',
                    $result['url'] ?? null
                );

                Log::info("Published post {$this->post->id} to {$channel->type}", $result);

            } catch (\Exception $e) {
                $postChannel->markAsFailed($e->getMessage());
                Log::error("Failed to publish post {$this->post->id} to {$channel->type}: " . $e->getMessage());
            }
        }

        // Update overall post status
        $publishedCount = $postChannels->where('status', 'published')->count();
        $totalCount = $postChannels->count();
        
        if ($publishedCount === $totalCount) {
            $this->post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        } elseif ($publishedCount > 0) {
            $this->post->update(['status' => 'partially_published']);
        } else {
            $this->post->update(['status' => 'failed']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->post->update(['status' => 'failed']);
        Log::error("PublishSocialPostJob failed for post {$this->post->id}: " . $exception->getMessage());
    }
}
