<?php

namespace App\Services;

use App\Models\AiBot;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KnowledgeSearchService
{
    /**
     * Search for relevant knowledge chunks for a bot's query
     */
    public function search(AiBot $bot, string $query, int $limit = 5): Collection
    {
        try {
            // Get bot's knowledge bases
            $knowledgeBaseIds = $bot->knowledgeBases()->pluck('knowledge_bases.id');

            if ($knowledgeBaseIds->isEmpty()) {
                Log::info("Bot {$bot->id} has no knowledge bases attached");
                return collect([]);
            }

            // Get tenant API key
            $tenant = \App\Models\Tenant::find($bot->tenant_id);
            $apiKey = $tenant->openai_key ?? config('services.openai.key');
            
            // Generate embedding for the query
            $embeddingService = new EmbeddingService($apiKey);
            $queryEmbedding = $embeddingService->generate($query);

            // Get all chunks from bot's knowledge bases
            $chunks = KnowledgeChunk::whereIn('knowledge_base_id', $knowledgeBaseIds)
                ->whereNotNull('embedding')
                ->get();

            if ($chunks->isEmpty()) {
                Log::info("No chunks with embeddings found for bot {$bot->id}");
                return collect([]);
            }

            // Calculate similarity scores
            $chunksWithScores = $chunks->map(function ($chunk) use ($queryEmbedding, $embeddingService) {
                $chunkEmbedding = $chunk->embedding;
                
                if (!is_array($chunkEmbedding)) {
                    return null;
                }

                $similarity = $embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding);
                
                return [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            })->filter()->sortByDesc('similarity')->take($limit);

            Log::info("Found " . $chunksWithScores->count() . " relevant chunks for query: " . substr($query, 0, 50));

            return $chunksWithScores;

        } catch (\Exception $e) {
            Log::error("Knowledge search failed: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Format search results as context for bot
     */
    public function formatAsContext(Collection $results): string
    {
        if ($results->isEmpty()) {
            return '';
        }

        $context = "Relevant information from knowledge base:\n\n";

        foreach ($results as $result) {
            $chunk = $result['chunk'];
            $similarity = round($result['similarity'] * 100, 1);
            
            $context .= "---\n";
            $context .= $chunk->content . "\n";
            $context .= "(Relevance: {$similarity}%)\n\n";
        }

        return $context;
    }

    /**
     * Search and format in one call
     */
    public function searchAndFormat(AiBot $bot, string $query, int $limit = 5): string
    {
        $results = $this->search($bot, $query, $limit);
        return $this->formatAsContext($results);
    }
}
