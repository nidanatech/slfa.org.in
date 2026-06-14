<?php

use App\Models\AiBot;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\PipelineStage;
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
    
    public function __construct($bot) {
        $this->id = $bot->id;
        $this->tenant_id = $bot->tenant_id;
        $this->actions = $bot->actions;
    }
    
    public function testExecute($toolCalls, $conversation) {
        return $this->executeTools($toolCalls, $conversation);
    }
}

try {
    $bot = AiBot::with('actions')->first();
    if (!$bot) die("No bot found.\n");

    echo "Testing with Bot: " . $bot->name . "\n";
    
    // 1. Setup Data
    $contact = Contact::where('tenant_id', $bot->tenant_id)->first();
    $channel = \App\Models\Channel::where('tenant_id', $bot->tenant_id)->first();
    
    if (!$contact || !$channel) die("Contact/Channel missing.\n");

    $conversation = Conversation::create([
        'tenant_id' => $bot->tenant_id,
        'contact_id' => $contact->id,
        'channel_id' => $channel->id,
        'status' => 'open',
    ]);
    
    // 2. Setup Pipeline
    $pipeline = Pipeline::create([
        'tenant_id' => $bot->tenant_id,
        'name' => 'Test Sales Pipeline',
        'object_type' => 'opportunity',
        'is_default' => false,
        'created_by' => 1 // Assuming user 1 exists
    ]);
    
    $stage = PipelineStage::create([
        'pipeline_id' => $pipeline->id, // Removed tenant_id as it's not in fillable
        'name' => 'New Lead',
        'order_index' => 1
    ]);
    
    echo "Debug: Pipeline ID {$pipeline->id} Created. Stage ID {$stage->id} Created.\n";
    echo "Debug: Pipeline Stages Count: " . $pipeline->stages()->count() . "\n";


    // 3. Setup Action
    $action = $bot->actions->where('action_name', 'Trigger Workflow')->first();
    if (!$action) {
        $action = \App\Models\BotAction::create([
             'tenant_id' => $bot->tenant_id,
             'bot_id' => $bot->id,
             'action_type' => 'trigger_workflow', // or whatever key is used
             'action_name' => 'Trigger Workflow',
             'is_active' => true,
             'config_json' => []
        ]);
    }
    
    // Configure Action
    $action->update([
        'config_json' => [
            'pipeline_id' => $pipeline->id,
            'trigger_instruction' => 'Create opportunity when user shows interest.'
        ]
    ]);
    $bot->load('actions');

    $simulator = new BotSimulator($bot);

    // TEST: Trigger Workflow
    echo "\n--- Test: Trigger Workflow ---\n";
    $toolCall = [
        'id' => 'call_wf_1',
        'function' => [
            'name' => 'trigger_workflow_1',
            'arguments' => '{}'
        ]
    ];
    $output = $simulator->testExecute([$toolCall], $conversation);
    print_r($output);
    
    // Check for error
    if (isset($output[0]['output'])) {
        $res = json_decode($output[0]['output'], true);
        print_r($res);
        if (isset($res['status']) && $res['status'] === 'error') {
            echo "Tool Execution Failed: " . $res['message'] . "\n";
        }
    }
    
    // Verify Opportunity
    $opp = \App\Models\Opportunity::where('pipeline_id', $pipeline->id)
        ->where('stage_id', $stage->id)
        ->where('contact_id', $contact->id)
        ->orderByDesc('created_at')
        ->first();
        
    if ($opp) {
        echo "Success: Opportunity Created ID {$opp->id} Name: {$opp->name}\n";
    } else {
        echo "Failure: No opportunity found.\n";
    }

    // Cleanup
    $conversation->delete();
    $opp?->delete();
    $stage->delete();
    $pipeline->delete();
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
