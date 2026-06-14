<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Http\Controllers\GoogleBusinessChannelController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshGmbData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('GMB Data Refresh Job Started');

        $gmbChannels = Channel::where('type', 'google_business')
            ->where('is_active', true)
            ->get();

        foreach ($gmbChannels as $channel) {
            try {
                Log::info('Refreshing GMB data for channel', ['channel_id' => $channel->id]);
                
                $controller = app(GoogleBusinessChannelController::class);
                
                // Call the refresh method via reflection to access private methods
                $reflection = new \ReflectionClass($controller);
                
                $ensureValidToken = $reflection->getMethod('ensureValidToken');
                $ensureValidToken->setAccessible(true);
                $ensureValidToken->invoke($controller, $channel);
                
                $fetchInsights = $reflection->getMethod('fetchAndStoreInsights');
                $fetchInsights->setAccessible(true);
                $fetchInsights->invoke($controller, $channel);
                
                $fetchReviews = $reflection->getMethod('fetchAndStoreReviews');
                $fetchReviews->setAccessible(true);
                $fetchReviews->invoke($controller, $channel);
                
                $fetchPosts = $reflection->getMethod('fetchAndStorePosts');
                $fetchPosts->setAccessible(true);
                $fetchPosts->invoke($controller, $channel);
                
                $fetchQuestions = $reflection->getMethod('fetchAndStoreQuestions');
                $fetchQuestions->setAccessible(true);
                $fetchQuestions->invoke($controller, $channel);
                
                Log::info('GMB data refreshed successfully', ['channel_id' => $channel->id]);
                
            } catch (\Exception $e) {
                Log::error('GMB Data Refresh Failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('GMB Data Refresh Job Completed');
    }
}
