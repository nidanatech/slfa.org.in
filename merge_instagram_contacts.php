<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

echo "Starting Instagram Contact Merge...\n";

$duplicates = Contact::select('channel_id', 'external_id', DB::raw('count(*) as count'))
    ->groupBy('channel_id', 'external_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "No duplicate contacts found.\n";
    exit;
}

echo "Found " . $duplicates->count() . " sets of duplicate contacts.\n";

foreach ($duplicates as $dup) {
    echo "Merging for Channel: {$dup->channel_id}, ExtID: {$dup->external_id}\n";
    
    $contacts = Contact::where('channel_id', $dup->channel_id)
        ->where('external_id', $dup->external_id)
        ->orderBy('created_at', 'asc')
        ->get();
        
    $mainContact = $contacts->shift(); // Keep the oldest one
    echo "  Main Contact ID: {$mainContact->id} ({$mainContact->name})\n";
    
    foreach ($contacts as $otherContact) {
        echo "  Processing duplicate Contact ID: {$otherContact->id} ({$otherContact->name})\n";
        
        // 1. Better Profile Info? (Take avatar if main is missing)
        if (!$mainContact->avatar_url && $otherContact->avatar_url) {
            $mainContact->update(['avatar_url' => $otherContact->avatar_url]);
        }
        
        // 2. Merge Conversations
        $otherConversations = Conversation::where('contact_id', $otherContact->id)->get();
        foreach ($otherConversations as $otherConv) {
            // Find if main already has a conversation for this channel
            $mainConv = Conversation::where('contact_id', $mainContact->id)
                ->where('channel_id', $otherConv->channel_id)
                ->where('conversation_type', $otherConv->conversation_type)
                ->first();
                
            if ($mainConv && $mainConv->id !== $otherConv->id) {
                echo "    Moving messages from Conv {$otherConv->id} to Main Conv {$mainConv->id}\n";
                Message::where('conversation_id', $otherConv->id)->update(['conversation_id' => $mainConv->id]);
                $otherConv->delete();
            } else {
                echo "    Reassigning Conv {$otherConv->id} to Main Contact {$mainContact->id}\n";
                $otherConv->update(['contact_id' => $mainContact->id]);
            }
        }
        
        // 3. Delete Duplicate Contact
        $otherContact->delete();
        echo "    Contact {$otherContact->id} deleted.\n";
    }
    
    echo "  Merge complete for this set.\n";
}

echo "Cleanup finished successfully.\n";
