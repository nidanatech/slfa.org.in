<?php

namespace App\Jobs;

use App\Models\Competitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorCompetitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $competitor;

    public function __construct(Competitor $competitor)
    {
        $this->competitor = $competitor;
    }

    public function handle(): void
    {
        Log::info("Starting monitoring for competitor: {$this->competitor->name}");

        // Get all active sources for this competitor
        $sources = $this->competitor->getActiveSources();

        if ($sources->isEmpty()) {
            Log::warning("No active sources found for competitor: {$this->competitor->name}");
            return;
        }

        // Dispatch scraping job for each source
        foreach ($sources as $source) {
            if ($source->needsScraping()) {
                ScrapeCompetitorSourceJob::dispatch($source);
                Log::info("Dispatched scrape job for source: {$source->url}");
            }
        }

        // Update last monitored timestamp
        $this->competitor->updateLastMonitored();

        Log::info("Completed monitoring for competitor: {$this->competitor->name}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("MonitorCompetitorJob failed for competitor {$this->competitor->id}: " . $exception->getMessage());
    }
}
