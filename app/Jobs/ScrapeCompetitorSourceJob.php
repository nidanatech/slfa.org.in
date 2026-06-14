<?php

namespace App\Jobs;

use App\Models\CompetitorSource;
use App\Models\CompetitorSnapshot;
use App\Models\CompetitorChange;
use App\Services\WebScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeCompetitorSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $source;

    public function __construct(CompetitorSource $source)
    {
        $this->source = $source;
    }

    public function handle(WebScraperService $scraper): void
    {
        Log::info("Scraping source: {$this->source->url}");

        // Scrape the URL
        $result = $scraper->scrapeUrl($this->source->url);

        if (!$result['success']) {
            Log::error("Failed to scrape {$this->source->url}: {$result['error']}");
            return;
        }

        // Extract metadata
        $metadata = $scraper->extractMetadata($result['html']);

        // Create snapshot
        $snapshot = CompetitorSnapshot::create([
            'source_id' => $this->source->id,
            'snapshot_type' => 'full',
            'content_hash' => $result['content_hash'],
            'raw_content' => $result['html'],
            'parsed_data' => $metadata,
            'metadata' => $result['metadata'],
            'captured_at' => now(),
        ]);

        Log::info("Created snapshot {$snapshot->id} for source {$this->source->id}");

        // Get previous snapshot
        $previousSnapshot = $this->source->snapshots()
            ->where('id', '!=', $snapshot->id)
            ->latest('captured_at')
            ->first();

        if ($previousSnapshot) {
            // Detect changes
            $changes = $scraper->detectChanges(
                $previousSnapshot->raw_content,
                $snapshot->raw_content
            );

            if ($changes['changed']) {
                // Create change record
                $change = CompetitorChange::create([
                    'source_id' => $this->source->id,
                    'competitor_id' => $this->source->competitor_id,
                    'change_type' => 'content',
                    'category' => 'website_update',
                    'severity' => $this->determineSeverity($changes),
                    'title' => 'Website content updated',
                    'description' => $this->generateChangeDescription($changes),
                    'before_snapshot_id' => $previousSnapshot->id,
                    'after_snapshot_id' => $snapshot->id,
                    'diff_data' => $changes,
                    'detected_at' => now(),
                ]);

                Log::info("Detected change {$change->id} for competitor {$this->source->competitor_id}");

                // Dispatch AI analysis job
                AnalyzeCompetitorChangeJob::dispatch($change);
            } else {
                Log::info("No changes detected for source {$this->source->id}");
            }
        }

        // Update last scraped timestamp
        $this->source->updateLastScraped();
    }

    private function determineSeverity(array $changes): string
    {
        $changeCount = count($changes['changes'] ?? []);

        if ($changeCount >= 5) {
            return 'high';
        } elseif ($changeCount >= 2) {
            return 'medium';
        }

        return 'low';
    }

    private function generateChangeDescription(array $changes): string
    {
        $descriptions = [];

        foreach ($changes['changes'] ?? [] as $change) {
            $descriptions[] = ucfirst($change['type']) . " changed";
        }

        return implode(', ', $descriptions) ?: 'Content updated';
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ScrapeCompetitorSourceJob failed for source {$this->source->id}: " . $exception->getMessage());
    }
}
