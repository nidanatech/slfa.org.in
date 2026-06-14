<?php

namespace App\Jobs;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduledPostPublisherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $posts = SocialPost::dueForPublishing()->get();

        Log::info("Found {$posts->count()} posts due for publishing");

        foreach ($posts as $post) {
            // Check if approval is required and post is approved
            $settings = \App\Models\SocialPlannerSettings::getOrCreateForTenant($post->tenant_id);
            
            if ($settings->requires_approval && $post->approval_status !== 'approved') {
                Log::warning("Post {$post->id} requires approval but is not approved");
                continue;
            }

            // Apply post delay if configured
            if ($settings->default_post_delay_minutes > 0) {
                PublishSocialPostJob::dispatch($post)
                    ->delay(now()->addMinutes($settings->default_post_delay_minutes));
            } else {
                PublishSocialPostJob::dispatch($post);
            }

            Log::info("Dispatched publish job for post {$post->id}");
        }
    }
}
