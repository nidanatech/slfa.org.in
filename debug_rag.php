<?php

use App\Models\AiBot;
use App\Models\KnowledgeChunk;
use App\Services\KnowledgeSearchService;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Knowledge Base RAG Debug ===\n\n";

    // Get the bot
    $bot = AiBot::find(7);
    if (!$bot) {
        echo "Bot not found!\n";
        exit;
    }

    echo "Bot: {$bot->name} (ID: {$bot->id})\n";
    echo "Tenant ID: {$bot->tenant_id}\n\n";

    // Check attached knowledge bases
    $kbs = $bot->knowledgeBases;
    echo "Attached Knowledge Bases: " . $kbs->count() . "\n";
    foreach ($kbs as $kb) {
        echo "  - {$kb->name} (ID: {$kb->id})\n";
    }
    echo "\n";

    // Check chunks
    $kbIds = $bot->knowledgeBases()->pluck('knowledge_bases.id');
    echo "Knowledge Base IDs: " . $kbIds->implode(', ') . "\n\n";

    $totalChunks = KnowledgeChunk::whereIn('knowledge_base_id', $kbIds)->count();
    $chunksWithEmbeddings = KnowledgeChunk::whereIn('knowledge_base_id', $kbIds)
        ->whereNotNull('embedding')
        ->count();

    echo "Total chunks: {$totalChunks}\n";
    echo "Chunks with embeddings: {$chunksWithEmbeddings}\n\n";

    // Show sample chunks
    echo "=== Sample Chunks ===\n";
    $sampleChunks = KnowledgeChunk::whereIn('knowledge_base_id', $kbIds)
        ->whereNotNull('embedding')
        ->limit(3)
        ->get();

    foreach ($sampleChunks as $chunk) {
        echo "Chunk ID: {$chunk->id}\n";
        echo "Content preview: " . substr($chunk->content, 0, 200) . "...\n";
        echo "Has embedding: " . (is_array($chunk->embedding) ? "YES (" . count($chunk->embedding) . " dims)" : "NO") . "\n";
        echo "---\n";
    }

    // Test search
    echo "\n=== Testing Search ===\n";
    $query = "intermediary bank details";
    echo "Query: {$query}\n\n";

    $searchService = new KnowledgeSearchService();
    $results = $searchService->search($bot, $query, 3);

    echo "Results found: " . $results->count() . "\n\n";

    foreach ($results as $result) {
        echo "Similarity: " . round($result['similarity'] * 100, 2) . "%\n";
        echo "Content: " . substr($result['chunk']->content, 0, 300) . "...\n";
        echo "---\n";
    }

    // Test formatted context
    echo "\n=== Formatted Context ===\n";
    $context = $searchService->formatAsContext($results);
    echo $context;

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
