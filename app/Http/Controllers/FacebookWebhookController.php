<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    /**
     * Verify webhook (GET request from Facebook)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.facebook.webhook_verify_token');

        Log::info('Facebook Webhook Verification Attempt', [
            'mode' => $mode,
            'received_token' => $token,
            'expected_token' => $verifyToken,
            'challenge' => $challenge,
            'token_match' => $token === $verifyToken,
            'all_params' => $request->all()
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Facebook Webhook Verified Successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Facebook Webhook Verification Failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
            'mode_check' => $mode === 'subscribe'
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle webhook events (POST request from Facebook)
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::error('Facebook Webhook Invalid Signature', [
                'signature' => $request->header('X-Hub-Signature-256'),
                'payload_length' => strlen($request->getContent())
            ]);
            return response('Invalid signature', 403);
        }

        Log::info('Facebook Webhook Signature Verified');

        // Process each entry
        foreach ($data['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;

            Log::info('Facebook Webhook Processing Entry', [
                'page_id' => $pageId,
                'has_messaging' => isset($entry['messaging']),
                'has_changes' => isset($entry['changes']),
                'messaging_count' => count($entry['messaging'] ?? []),
                'changes_count' => count($entry['changes'] ?? [])
            ]);

            // Process messaging events
            if (isset($entry['messaging'])) {
                foreach ($entry['messaging'] as $event) {
                    Log::info('Facebook Webhook Dispatching Message Event Job', [
                        'page_id' => $pageId,
                        'event_type' => array_keys($event)
                    ]);
                    $this->processMessagingEvent($pageId, $event);
                }
            }

            // Process feed changes (comments)
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    Log::info('Facebook Webhook Processing Change', [
                        'page_id' => $pageId,
                        'field' => $change['field'] ?? null,
                        'value_item' => $change['value']['item'] ?? null
                    ]);
                    $this->processFeedChange($pageId, $change);
                }
            }
        }

        Log::info('Facebook Webhook Processing Complete');

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $appSecret = config('services.facebook.client_secret');
        $payload = $request->getContent();
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process messaging event
     */
    protected function processMessagingEvent(string $pageId, array $event)
    {
        if (isset($event['message'])) {
            // Dispatch message processing
            \App\Jobs\ProcessFacebookMessage::dispatch($pageId, $event);
        }

        if (isset($event['delivery']) || isset($event['read'])) {
            // Dispatch status update
            \App\Jobs\ProcessMessagingStatusUpdate::dispatch('facebook', $event);
        }
    }

    /**
     * Process feed change (comment)
     */
    protected function processFeedChange(string $pageId, array $change)
    {
        if ($change['field'] === 'feed' && $change['value']['item'] === 'comment') {
            // Dispatch job for async processing
            \App\Jobs\ProcessFacebookComment::dispatch($pageId, $change['value']);
        }
    }
}
