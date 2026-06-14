<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class WebScraperService
{
    /**
     * Scrape URL and return content
     */
    public function scrapeUrl(string $url): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CompetitorPulse/1.0)',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: Failed to fetch URL");
            }

            $html = $response->body();
            $contentHash = hash('sha256', $html);

            return [
                'success' => true,
                'html' => $html,
                'content_hash' => $contentHash,
                'metadata' => [
                    'status_code' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'content_length' => strlen($html),
                    'scraped_at' => now()->toIso8601String(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract metadata from HTML
     */
    public function extractMetadata(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $metadata = [
            'title' => $this->getMetaTag($xpath, '//title'),
            'description' => $this->getMetaTag($xpath, '//meta[@name="description"]/@content'),
            'keywords' => $this->getMetaTag($xpath, '//meta[@name="keywords"]/@content'),
            'og_title' => $this->getMetaTag($xpath, '//meta[@property="og:title"]/@content'),
            'og_description' => $this->getMetaTag($xpath, '//meta[@property="og:description"]/@content'),
            'og_image' => $this->getMetaTag($xpath, '//meta[@property="og:image"]/@content'),
        ];

        return array_filter($metadata);
    }

    /**
     * Extract specific content based on type
     */
    public function extractContent(string $html, string $type): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        return match($type) {
            'pricing' => $this->extractPricing($xpath),
            'features' => $this->extractFeatures($xpath),
            'jobs' => $this->extractJobs($xpath),
            'blog' => $this->extractBlogPosts($xpath),
            default => [],
        };
    }

    /**
     * Detect changes between old and new HTML
     */
    public function detectChanges(string $oldHtml, string $newHtml): array
    {
        // Quick hash comparison
        $oldHash = hash('sha256', $oldHtml);
        $newHash = hash('sha256', $newHtml);

        if ($oldHash === $newHash) {
            return ['changed' => false];
        }

        // Extract and compare key elements
        $oldMeta = $this->extractMetadata($oldHtml);
        $newMeta = $this->extractMetadata($newHtml);

        $changes = [];

        // Check title changes
        if (($oldMeta['title'] ?? '') !== ($newMeta['title'] ?? '')) {
            $changes[] = [
                'type' => 'title',
                'old' => $oldMeta['title'] ?? '',
                'new' => $newMeta['title'] ?? '',
            ];
        }

        // Check description changes
        if (($oldMeta['description'] ?? '') !== ($newMeta['description'] ?? '')) {
            $changes[] = [
                'type' => 'description',
                'old' => $oldMeta['description'] ?? '',
                'new' => $newMeta['description'] ?? '',
            ];
        }

        return [
            'changed' => true,
            'changes' => $changes,
            'old_hash' => $oldHash,
            'new_hash' => $newHash,
        ];
    }

    /**
     * Validate if URL is scrapable
     */
    public function validateUrl(string $url): bool
    {
        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check robots.txt (simplified)
        try {
            $parsedUrl = parse_url($url);
            $robotsUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}/robots.txt";
            
            $response = Http::timeout(5)->get($robotsUrl);
            if ($response->successful()) {
                $robots = $response->body();
                // Simple check - in production, use a proper robots.txt parser
                if (stripos($robots, 'Disallow: /') !== false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            // If robots.txt doesn't exist, assume it's okay
        }

        return true;
    }

    /**
     * Helper: Get meta tag value
     */
    private function getMetaTag(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue ?? $nodes->item(0)->value ?? '');
        }
        return null;
    }

    /**
     * Extract pricing information
     */
    private function extractPricing(DOMXPath $xpath): array
    {
        $prices = [];
        
        // Look for common pricing patterns
        $priceNodes = $xpath->query('//*[contains(@class, "price") or contains(@class, "pricing")]');
        
        foreach ($priceNodes as $node) {
            $text = trim($node->nodeValue);
            if (preg_match('/\$\d+/', $text)) {
                $prices[] = $text;
            }
        }

        return ['prices' => array_unique($prices)];
    }

    /**
     * Extract features
     */
    private function extractFeatures(DOMXPath $xpath): array
    {
        $features = [];
        
        // Look for feature lists
        $featureNodes = $xpath->query('//ul[contains(@class, "feature")]/li | //div[contains(@class, "feature")]');
        
        foreach ($featureNodes as $node) {
            $features[] = trim($node->nodeValue);
        }

        return ['features' => array_filter($features)];
    }

    /**
     * Extract job postings
     */
    private function extractJobs(DOMXPath $xpath): array
    {
        $jobs = [];
        
        // Look for job listings
        $jobNodes = $xpath->query('//*[contains(@class, "job") or contains(@class, "career") or contains(@class, "position")]');
        
        foreach ($jobNodes as $node) {
            $jobs[] = trim($node->nodeValue);
        }

        return ['jobs' => array_filter($jobs)];
    }

    /**
     * Extract blog posts
     */
    private function extractBlogPosts(DOMXPath $xpath): array
    {
        $posts = [];
        
        // Look for blog post titles
        $postNodes = $xpath->query('//article//h2 | //article//h3 | //*[contains(@class, "post-title")]');
        
        foreach ($postNodes as $node) {
            $posts[] = trim($node->nodeValue);
        }

        return ['posts' => array_filter($posts)];
    }
}
