<?php
use App\Models\Channel;
use App\Models\User;

// Assuming user ID 1 or first user
$user = User::first();
if ($user && $user->tenant) {
    echo "Tenant: " . $user->tenant->name . "\n";
    $channels = $user->tenant->channels;
    foreach ($channels as $channel) {
        echo "Channel ID: " . $channel->id . "\n";
        echo "Name: " . $channel->name . "\n";
        echo "Type: " . $channel->type . "\n";
        echo "Config Type: " . gettype($channel->config) . "\n";
        echo "Config: " . json_encode($channel->config) . "\n";
        echo "-------------------\n";
    }
} else {
    echo "No user or tenant found.\n";
}
