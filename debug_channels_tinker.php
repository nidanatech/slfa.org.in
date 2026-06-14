$channels = App\Models\Channel::all();
foreach ($channels as $channel) {
    echo "ID: {$channel->id} | Type: {$channel->type} | Config: " . json_encode($channel->config) . "\n";
}
