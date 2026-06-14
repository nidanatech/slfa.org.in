<?php

use App\Models\AiBot;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Channel;
use App\Models\BotSession;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class BotSimulator {
    use \App\Traits\HandlesBotConversations;

    public $id;
    public $actions;
    public $tenant_id;
    public $tenant;
    public $assistant_id = 'mock_asst_id';
    
    public function __construct($bot) {
        $this->id = $bot->id;
        $this->tenant_id = $bot->tenant_id;
        $this->tenant = $bot->tenant;
        $this->actions = $bot->actions;
    }
    
    public function testExecute($toolCalls, $conversation) {
        return $this->executeTools($toolCalls, $conversation);
    }
}

try {
    // 1. Setup Tenant/User
    $tenant = \App\Models\Tenant::first();
    $user = \App\Models\User::first();
    
    if (!$tenant) die("Tenant missing.\n");

    echo "--- Setting up Bots ---\n";
    // 2. Create Source Bot
    $sourceBot = AiBot::create([
        'tenant_id' => $tenant->id,
        'name' => 'Source Bot',
        'model' => 'gpt-4',
        'prompt' => 'You are source.',
        'is_active' => true,
        'supported_channels' => []
    ]);

    // 3. Create Target Bot
    $targetBot = AiBot::create([
        'tenant_id' => $tenant->id,
        'name' => 'Target Bot',
        'model' => 'gpt-4',
        'prompt' => 'You are target.',
        'is_active' => true,
        'supported_channels' => []
    ]);
    
    // 4. Create Contact/Channel/Conversation/Tag
    $channel = Channel::create(['tenant_id' => $tenant->id, 'type' => 'webchat', 'name' => 'Test Channel ' . uniqid()]);
    $contact = Contact::create([
        'tenant_id' => $tenant->id, 
        'channel_id' => $channel->id, 
        'name' => 'Test Visitor',
        'external_id' => 'vis_' . uniqid() // Ensure uniqueness
    ]);
    $tag = \App\Models\Tag::create(['tenant_id' => $tenant->id, 'name' => 'Transferred Tag', 'color' => '#000000']);
    
    $conversation = Conversation::create([
        'tenant_id' => $tenant->id, 
        'contact_id' => $contact->id, 
        'channel_id' => $channel->id, 
        'status' => 'open'
    ]);
    
    // Add dummy messages for context verification
    \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound', // user
        'content' => 'First message'
    ]);
    \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound', // user
        'content' => 'Trigger message'
    ]);

    // 5. Start Session for Source Bot manually
    BotSession::create([
        'tenant_id' => $tenant->id,
        'bot_id' => $sourceBot->id,
        'conversation_id' => $conversation->id,
        'started_at' => now(),
        'channel' => 'webchat'
    ]);
    
    echo "Initial Session Created for Bot {$sourceBot->id}\n";

    // 6. Configure Transfer Action on Source Bot
    $action = \App\Models\BotAction::create([
         'tenant_id' => $tenant->id,
         'bot_id' => $sourceBot->id,
         'action_type' => 'transfer_bot',
         'action_name' => 'Transfer Bot',
         'is_active' => true,
         'config_json' => [
             'scenarios' => [
                 [
                     'is_active' => true,
                     'target_bot_id' => $targetBot->id,
                     'trigger_config_json' => ['instruction' => 'Ask for help'],
                     'scenario_name' => 'Help Request',
                     'add_contact_tag_id' => $tag->id
                 ]
             ]
         ]
    ]);
    $sourceBot->load('actions');
    
    // 7. Execute Transfer
    $simulator = new BotSimulator($sourceBot);
    $toolCall = [
        'id' => 'call_transfer_1',
        'function' => [
            'name' => 'transfer_to_bot_1',
            'arguments' => '{}'
        ]
    ];
    
    echo "--- Executing Transfer Tool ---\n";
    $output = $simulator->testExecute([$toolCall], $conversation);
    print_r(($output[0]['output'] ?? 'No Output'));
    echo "\n";
    
    // 8. Verify Sessions
    $closedSession = BotSession::where('bot_id', $sourceBot->id)
        ->where('conversation_id', $conversation->id)
        ->whereNotNull('ended_at')
        ->first();
        
    $newSession = BotSession::where('bot_id', $targetBot->id)
        ->where('conversation_id', $conversation->id)
        ->whereNull('ended_at')
        ->first();
        
    $contact->refresh();
    $hasTag = $contact->tags->contains($tag->id);

    if ($closedSession && $newSession && $hasTag) {
        echo "SUCCESS: Session switched. Tag Applied. (Output: " . ($output[0]['output']['message'] ?? 'N/A') . ")\n";
    } else {
        echo "FAILURE: Sessions not updated correctly or Tag missing.\n";
        echo "Closed Session: " . ($closedSession ? 'Yes' : 'No') . "\n";
        echo "New Session: " . ($newSession ? 'Yes' : 'No') . "\n";
        echo "Has Tag: " . ($hasTag ? 'Yes' : 'No') . "\n";
    }

    // Cleanup
    $conversation->delete();
    $contact->delete();
    $channel->delete();
    $action->delete();
    $sourceBot->delete();
    $targetBot->delete();
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
