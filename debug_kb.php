<?php

use App\Models\AiBot;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Knowledge Base Debug ===\n\n";

    // Check bots
    $bots = AiBot::all();
    echo "Total Bots: " . $bots->count() . "\n\n";

    foreach ($bots as $bot) {
        echo "Bot: {$bot->name} (ID: {$bot->id})\n";
        
        // Check attached knowledge bases
        $kbs = $bot->knowledgeBases;
        echo "  Attached Knowledge Bases: " . $kbs->count() . "\n";
        
        foreach ($kbs as $kb) {
            echo "    - {$kb->name} (ID: {$kb->id})\n";
            
            // Check chunks with embeddings
            $totalChunks = KnowledgeChunk::where('knowledge_base_id', $kb->id)->count();
            $chunksWithEmbeddings = KnowledgeChunk::where('knowledge_base_id', $kb->id)
                ->whereNotNull('embedding')
                ->count();
            
            echo "      Total chunks: {$totalChunks}\n";
            echo "      Chunks with embeddings: {$chunksWithEmbeddings}\n";
            
            // Show first chunk preview
            $firstChunk = KnowledgeChunk::where('knowledge_base_id', $kb->id)->first();
            if ($firstChunk) {
                echo "      First chunk preview: " . substr($firstChunk->content, 0, 100) . "...\n";
                echo "      Has embedding: " . ($firstChunk->embedding ? "YES" : "NO") . "\n";
            }
        }
        echo "\n";
    }

    // Check all knowledge bases
    echo "\n=== All Knowledge Bases ===\n";
    $allKBs = KnowledgeBase::withCount('sources')->get();
    foreach ($allKBs as $kb) {
        echo "KB: {$kb->name} (ID: {$kb->id})\n";
        echo "  Sources: {$kb->sources_count}\n";
        echo "  Attached to bots: " . $kb->bots()->count() . "\n\n";
    }

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
