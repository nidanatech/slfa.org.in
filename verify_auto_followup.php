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
    
    // Expose protected method for testing
    public function testSchedule($conversation) {
        $this->scheduleNextFollowup($conversation);
    }
}

try {
    // 1. Setup Tenant
    $tenant = \App\Models\Tenant::first();
    if (!$tenant) die("Tenant missing.\n");

    echo "--- Setting up Bot ---\n";
    $bot = AiBot::create([
        'tenant_id' => $tenant->id,
        'name' => 'Followup Bot',
        'model' => 'gpt-4o',
        'prompt' => 'You are a bot.',
        'is_active' => true,
    ]);

    // 2. Configure Auto Follow-up Action (Nested Structure)
    $action = \App\Models\BotAction::create([
         'tenant_id' => $tenant->id,
         'bot_id' => $bot->id,
         'action_type' => 'auto_followup',
         'action_name' => 'Auto Follow-up',
         'is_active' => true,
         'config_json' => [
             'scenarios' => [
                 [
                     'scenario_name' => 'No Reply',
                     'sequences' => [
                         ['delay_minutes' => 10, 'message_content' => 'First poke'], // 10 mins
                         ['delay_minutes' => 60, 'message_content' => 'Second poke'] // 60 mins
                     ]
                 ]
             ]
         ]
    ]);
    $bot->load('actions');
    
    // 3. Setup Conversation
    $channel = Channel::create(['tenant_id' => $tenant->id, 'type' => 'webchat', 'name' => 'Test Channel ' . uniqid()]);
    $contact = Contact::create([
        'tenant_id' => $tenant->id, 
        'channel_id' => $channel->id, 
        'name' => 'Test Visitor',
        'external_id' => 'vis_' . uniqid()
    ]);
    
    $conversation = Conversation::create([
        'tenant_id' => $tenant->id, 
        'contact_id' => $contact->id, 
        'channel_id' => $channel->id, 
        'status' => 'open'
    ]);
    
    BotSession::create([
        'tenant_id' => $tenant->id,
        'bot_id' => $bot->id,
        'conversation_id' => $conversation->id,
        'started_at' => now(),
        'channel' => 'webchat',
        'thread_id' => 'mock_thread'
    ]);

    // 4. Test Scheduling (Simulate Bot Reply End)
    echo "--- Testing scheduleNextFollowup ---\n";
    $simulator = new BotSimulator($bot);
    $simulator->testSchedule($conversation);
    
    $conversation->refresh();
    if ($conversation->next_followup_at) {
        echo "SUCCESS: Next Followup Scheduled at: " . $conversation->next_followup_at . "\n";
    } else {
        echo "FAILURE: Next Followup NOT scheduled.\n";
    }

    // 5. Test Job Execution (Step 1)
    echo "--- Testing Job Execution (Step 1) ---\n";
    // Force time to be PAST the scheduled time
    $conversation->update(['next_followup_at' => now()->subMinutes(1)]);
    
    // Set created_at/last_message_at to match logic (10 mins ago)
    // Job logic relies on diffInMinutes(now). If we want Step 1 (10m) to trigger, 
    // we need last_message_at to be >= 10m ago.
    $conversation->update(['last_message_at' => now()->subMinutes(12)]);
    
    $job = new \App\Jobs\ProcessAutoFollowUps();
    $job->handle();
    
    $conversation->refresh();
    $msgs = $conversation->messages()->where('direction', 'outbound')->get();
    echo "Messages Count: " . $msgs->count() . "\n";
    if ($msgs->count() > 0 && $msgs->last()->content === 'First poke') {
        echo "SUCCESS: First message sent.\n";
    } else {
        echo "FAILURE: First message not sent.\n";
        print_r($msgs->toArray());
    }
    
    echo "Next Followup At: " . $conversation->next_followup_at . "\n";
    
    // 6. Test Job Execution (Step 2)
    echo "--- Testing Job Execution (Step 2) ---\n";
    // We expect next delay to be 60 mins (from last message? No, from last_message_at of USER? or Start?).
    // Logic: diffInMinutes(now - last_message_at).
    // If Step 1 was 10m, Step 2 is 60m.
    // So we need to simulate time passing so diff is > 60.
    
    $conversation->update(['next_followup_at' => now()->subMinutes(1)]);
    $conversation->update(['last_message_at' => now()->subMinutes(65)]);
    
    $job->handle();
    
    $conversation->refresh();
    $msgs = $conversation->messages()->where('direction', 'outbound')->get();
    if ($msgs->count() > 1 && $msgs->last()->content === 'Second poke') {
        echo "SUCCESS: Second message sent.\n";
    } else {
        echo "FAILURE: Second message not sent.\n";
    }

    // Cleanup
    $conversation->delete();
    $contact->delete();
    $channel->delete();
    $action->delete();
    $bot->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
