<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessThreadsMention;
use App\Jobs\ProcessThreadsModeration;
use App\Jobs\ProcessThreadsReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThreadsWebhookController extends Controller
{
    /**
     * Verify webhook (GET request from Threads)
     */
    public function verify(Request $request)
    {
        // Laravel converts dots to underscores in query parameters
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.threads.webhook_verify_token');

        Log::info('Threads Webhook Verification Attempt', [
            'mode' => $mode,
            'token_provided' => $token,
            'token_expected' => $verifyToken,
            'token_match' => $token === $verifyToken,
            'mode_check' => $mode === 'subscribe',
            'full_url' => $request->fullUrl(),
            'all_query_params' => $request->query->all(),
            'method' => $request->method()
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Threads Webhook Verified Successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::error('Threads Webhook Verification Failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
            'mode_check' => $mode === 'subscribe',
            'expected_token' => $verifyToken,
            'received_token' => $token
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle webhook events (POST request from Threads)
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        Log::info('Threads Webhook POST Received', [
            'has_data' => !empty($data),
            'entry_count' => count($data['entry'] ?? []),
            'headers' => [
                'signature' => $request->header('X-Hub-Signature-256'),
                'user_agent' => $request->header('User-Agent'),
            ],
            'full_payload' => $data
        ]);

        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::error('Threads Webhook Invalid Signature', [
                'signature' => $request->header('X-Hub-Signature-256'),
                'payload_length' => strlen($request->getContent())
            ]);
            return response('Invalid signature', 403);
        }

        Log::info('Threads Webhook Signature Verified');

        // Process each entry
        foreach ($data['entry'] ?? [] as $entry) {
            $threadsId = $entry['id'] ?? null;

            Log::info('Threads Webhook Processing Entry', [
                'threads_id' => $threadsId,
                'has_changes' => isset($entry['changes']),
                'changes_count' => count($entry['changes'] ?? [])
            ]);

            // Process changes
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $field = $change['field'] ?? null;
                    $value = $change['value'] ?? [];

                    Log::info('Threads Webhook Processing Change', [
                        'threads_id' => $threadsId,
                        'field' => $field,
                        'value_keys' => array_keys($value)
                    ]);

                    // Route event based on field type
                    if ($field === 'replies') {
                        // Someone replied to one of your threads
                        Log::info('Threads Webhook Dispatching Reply Job', [
                            'threads_id' => $threadsId,
                            'reply_id' => $value['id'] ?? null
                        ]);
                        ProcessThreadsReply::dispatch($threadsId, $value);
                    } elseif ($field === 'mentions') {
                        // Someone mentioned your account
                        Log::info('Threads Webhook Dispatching Mention Job', [
                            'threads_id' => $threadsId,
                            'mention_id' => $value['id'] ?? null
                        ]);
                        ProcessThreadsMention::dispatch($threadsId, $value);
                    } elseif (in_array($field, ['moderation', 'hide_status'])) {
                        // Moderation event (reply hidden/unhidden)
                        Log::info('Threads Webhook Dispatching Moderation Job', [
                            'threads_id' => $threadsId,
                            'field' => $field
                        ]);
                        ProcessThreadsModeration::dispatch($threadsId, $value);
                    } else {
                        Log::warning('Threads Webhook Unknown Field Type', [
                            'field' => $field,
                            'value' => $value
                        ]);
                    }
                }
            }
        }

        Log::info('Threads Webhook Processing Complete');

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $appSecret = config('services.threads.client_secret');
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
