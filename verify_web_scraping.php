<?php

use App\Models\KnowledgeBase;
use App\Models\KnowledgeSource;
use App\Jobs\ProcessKnowledgeSource;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Testing Web Scraping Functionality ===\n\n";

    // Find or create a test knowledge base
    $kb = KnowledgeBase::where('name', 'Test KB')->first();
    
    if (!$kb) {
        echo "Creating test knowledge base...\n";
        $kb = KnowledgeBase::create([
            'tenant_id' => 1,
            'name' => 'Test KB',
            'description' => 'For testing web scraping',
            'language' => 'en',
            'visibility' => 'bot',
        ]);
    }

    echo "Knowledge Base: {$kb->name} (ID: {$kb->id})\n\n";

    // Create a URL source
    echo "Creating URL source...\n";
    $source = $kb->sources()->create([
        'tenant_id' => 1,
        'type' => 'url',
        'name' => 'Example Website',
        'source_config_json' => [
            'url' => 'https://example.com'
        ],
        'is_active' => true,
    ]);

    echo "Source created: {$source->name} (ID: {$source->id})\n";
    echo "URL: {$source->source_config_json['url']}\n\n";

    // Process the source synchronously for testing
    echo "Processing source (web scraping)...\n";
    $job = new ProcessKnowledgeSource($source);
    $job->handle();

    echo "\n✓ Source processed successfully!\n";
    echo "Last synced: {$source->fresh()->last_synced_at}\n\n";

    // Check chunks
    $chunks = $source->chunks;
    echo "Chunks created: " . $chunks->count() . "\n";
    
    if ($chunks->count() > 0) {
        echo "\nFirst chunk preview:\n";
        echo "---\n";
        echo substr($chunks->first()->content, 0, 200) . "...\n";
        echo "---\n";
        echo "Token count: {$chunks->first()->token_count}\n";
    }

    // Cleanup
    echo "\nCleaning up test data...\n";
    $source->chunks()->delete();
    $source->delete();
    $kb->delete();

    echo "\n✓ Test completed successfully!\n";

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
