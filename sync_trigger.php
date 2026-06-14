<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Channel;
use App\Services\WhatsAppBaileysService;

$channel = Channel::where('type', 'whatsapp')->first();
if (!$channel) {
    echo "No WhatsApp channel found.\n";
    exit(1);
}

$sessionId = $channel->config['session_id'];
echo "Triggering sync for session: $sessionId\n";

$service = new WhatsAppBaileysService();
$result = $service->syncAll($sessionId);

print_r($result);
