<?php

use App\Models\AiBot;
use App\Models\Tenant;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "--- Setup ---\n";
    $tenant = Tenant::first();
    if (!$tenant) die("Tenant missing.\n");

    echo "--- Test 1: Create Bot A (Primary) ---\n";
    $botA = AiBot::create([
        'tenant_id' => $tenant->id,
        'name' => 'Bot A',
        'is_active' => true,
        'is_primary' => true,
        'model' => 'gpt-4o',
        'prompt' => 'test'
    ]);
    echo "Bot A Created. Primary: " . ($botA->is_primary ? 'Yes' : 'No') . "\n";

    echo "--- Test 2: Create Bot B (Primary) ---\n";
    $botB = AiBot::create([
        'tenant_id' => $tenant->id,
        'name' => 'Bot B',
        'is_active' => true,
        'is_primary' => true,
        'model' => 'gpt-4o',
        'prompt' => 'test'
    ]);
    echo "Bot B Created. Primary: " . ($botB->is_primary ? 'Yes' : 'No') . "\n";

    echo "--- Test 3: Verify Bot A is NO LONGER Primary ---\n";
    $botA->refresh();
    if (!$botA->is_primary && $botB->is_primary) {
        echo "SUCCESS: Bot A is no longer primary. Bot B took over.\n";
    } else {
        echo "FAILURE: \n";
        echo "Bot A Primary: " . $botA->is_primary . "\n";
        echo "Bot B Primary: " . $botB->is_primary . "\n";
    }

    echo "--- Test 4: Verify Controller Selection Logic ---\n";
    // Simulation of WebChatController Logic
    $bots = AiBot::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->orderByDesc('is_primary')
        ->get();
    
    $firstBot = $bots->first();
    if ($firstBot->id === $botB->id) {
        echo "SUCCESS: Bot B (Primary) is selected first.\n";
    } else {
        echo "FAILURE: Selected " . $firstBot->name . " (ID: " . $firstBot->id . ") instead of Bot B.\n";
    }

    // Cleanup
    $botA->delete();
    $botB->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
