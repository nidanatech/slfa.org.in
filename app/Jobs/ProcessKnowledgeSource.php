<?php

namespace App\Jobs;

use App\Models\KnowledgeSource;
use App\Models\KnowledgeChunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessKnowledgeSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function __construct(
        public KnowledgeSource $source
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Processing knowledge source: {$this->source->id} - Type: {$this->source->type}");

            match ($this->source->type) {
                'url' => $this->processUrl(),
                'document' => $this->processDocument(),
                'text', 'faq' => $this->processText(),
                default => Log::warning("Unknown source type: {$this->source->type}")
            };

            $this->source->update(['last_synced_at' => now()]);
            Log::info("Successfully processed source: {$this->source->id}");

        } catch (\Exception $e) {
            Log::error("Failed to process source {$this->source->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function processUrl(): void
    {
        $url = $this->source->source_config_json['url'] ?? null;
        
        if (!$url) {
            throw new \Exception('URL not found in source config');
        }

        // Fetch the webpage content
        $response = Http::timeout(30)->get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
        }

        $html = $response->body();
        
        // Extract text content from HTML
        $text = $this->extractTextFromHtml($html);
        
        // Create chunks from the text
        $this->createChunks($this->sanitizeUtf8($text));
    }

    private function processDocument(): void
    {
        $filePath = $this->source->source_config_json['file_path'] ?? null;
        
        if (!$filePath) {
            throw new \Exception('File path not found in source config');
        }

        $fullPath = storage_path('app/public/' . $filePath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found: ' . $fullPath);
        }

        $mimeType = $this->source->source_config_json['mime_type'] ?? '';
        
        $text = match (true) {
            str_contains($mimeType, 'text/plain') => file_get_contents($fullPath),
            str_contains($mimeType, 'pdf') => $this->extractTextFromPdf($fullPath),
            default => throw new \Exception('Unsupported file type: ' . $mimeType)
        };

        $this->createChunks($this->sanitizeUtf8($text));
    }

    private function processText(): void
    {
        $content = $this->source->source_config_json['content'] ?? null;
        
        if (!$content) {
            throw new \Exception('Content not found in source config');
        }

        $this->createChunks($this->sanitizeUtf8($content));
    }

    private function extractTextFromHtml(string $html): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Strip all HTML tags
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    private function extractTextFromPdf(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            
            $text = $pdf->getText();
            
            // Clean up the text
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (empty($text)) {
                Log::warning("PDF parsing returned empty text for: " . basename($filePath));
                return "Unable to extract text from PDF: " . basename($filePath);
            }
            
            Log::info("Successfully extracted " . strlen($text) . " characters from PDF");
            return $text;
            
        } catch (\Exception $e) {
            Log::error("PDF parsing failed: " . $e->getMessage());
            throw new \Exception("Failed to parse PDF: " . $e->getMessage());
        }
    }

    private function createChunks(string $text): void
    {
        // Delete existing chunks for this source
        $this->source->chunks()->delete();

        // Split text into chunks (simple word-based chunking)
        $chunkSize = 1000; // characters
        $overlap = 200; // characters overlap between chunks
        
        $chunks = [];
        $textLength = mb_strlen($text, 'UTF-8');
        $position = 0;

        while ($position < $textLength) {
            $chunk = mb_substr($text, $position, $chunkSize, 'UTF-8');
            
            if (!empty(trim($chunk))) {
                $chunks[] = trim($chunk);
            }
            
            $position += ($chunkSize - $overlap);
        }

        if (empty($chunks)) {
            Log::warning("No chunks created for source {$this->source->id}");
            return;
        }

        // Generate embeddings for all chunks in batch
        $tenant = \App\Models\Tenant::find($this->source->tenant_id);
        $apiKey = $tenant->openai_key ?? config('services.openai.key');
        
        $embeddingService = new \App\Services\EmbeddingService($apiKey);
        
        try {
            Log::info("Generating embeddings for " . count($chunks) . " chunks");
            $embeddings = $embeddingService->generateBatch($chunks);
            
            // Create chunk records with embeddings
            foreach ($chunks as $index => $chunkText) {
                KnowledgeChunk::create([
                    'tenant_id' => $this->source->tenant_id,
                    'knowledge_base_id' => $this->source->knowledge_base_id,
                    'source_id' => $this->source->id,
                    'content' => $chunkText,
                    'embedding' => $embeddings[$index] ?? null,
                    'token_count' => $this->estimateTokenCount($chunkText),
                ]);
            }

            Log::info("Created " . count($chunks) . " chunks with embeddings for source {$this->source->id}");
            
        } catch (\Exception $e) {
            Log::error("Failed to generate embeddings: " . $e->getMessage());
            
            // Create chunks without embeddings as fallback
            foreach ($chunks as $chunkText) {
                KnowledgeChunk::create([
                    'tenant_id' => $this->source->tenant_id,
                    'knowledge_base_id' => $this->source->knowledge_base_id,
                    'source_id' => $this->source->id,
                    'content' => $chunkText,
                    'embedding' => null,
                    'token_count' => $this->estimateTokenCount($chunkText),
                ]);
            }
            
            Log::warning("Created chunks without embeddings due to error");
        }
    }

    private function estimateTokenCount(string $text): int
    {
        // Rough estimation: ~4 characters per token
        return (int) ceil(mb_strlen($text, 'UTF-8') / 4);
    }

    /**
     * Sanitize string to ensure it's valid UTF-8
     */
    private function sanitizeUtf8(string $text): string
    {
        // This will strip out any invalid UTF-8 characters
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}
