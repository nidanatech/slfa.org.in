<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessGmbWebhookJob;
use Illuminate\Support\Facades\Log;

class GmbWebhookController extends Controller
{
    /**
     * Handle incoming Google Business Profile Pub/Sub notifications.
     */
    public function handle(Request $request)
    {
        // GMB notifications come as a JSON payload wrapping a Pub/Sub message
        $payload = $request->all();

        // Basic validation of Pub/Sub format
        if (!isset($payload['message']['data'])) {
            Log::warning('GMB Webhook: Invalid payload format', ['payload' => $payload]);
            return response()->json(['status' => 'ignored', 'reason' => 'invalidformat'], 400);
        }

        // Dispatch job to process asynchronously to avoid timeout
        ProcessGmbWebhookJob::dispatch($payload['message']);

        return response()->json(['status' => 'processed']);
    }
}
