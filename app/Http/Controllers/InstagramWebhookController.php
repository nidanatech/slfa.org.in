<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramComment;
use App\Jobs\ProcessInstagramMention;
use App\Jobs\ProcessInstagramMessage;
use App\Jobs\ProcessInstagramMessageEdit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    /**
     * Verify webhook (GET request from Meta)
     */
    public function verify(Request $request)
    {
        // Support both dots and underscores (Laravel usually converts dots to underscores)
        $mode = $request->input('hub_mode', $request->input('hub.mode'));
        $token = $request->input('hub_verify_token', $request->input('hub.verify_token'));
        $challenge = $request->input('hub_challenge', $request->input('hub.challenge'));

        $verifyToken = config('services.instagram.webhook_verify_token');

        Log::info('Instagram Webhook Verification Attempt', [
            'raw_all' => $request->all(),
            'mode_parsed' => $mode,
            'token_received' => $token,
            'token_expected' => $verifyToken,
            'token_match' => $token === $verifyToken,
            'mode_check' => $mode === 'subscribe'
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Instagram Webhook Verified Successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::error('Instagram Webhook Verification Failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle webhook events (POST request from Meta)
     */
    public function handle(Request $request)
    {
        $data = $request->all();


        Log::info('InstagramWebhookController: Processing Webhook Data');

        // Process each entry
        foreach ($data['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;

            Log::info('Instagram Webhook Processing Entry', [
                'page_id' => $pageId,
                'has_messaging' => isset($entry['messaging']),
                'has_changes' => isset($entry['changes'])
            ]);

            // Process messaging events (DMs)
            if (isset($entry['messaging'])) {
                foreach ($entry['messaging'] as $event) {
                    $this->processMessagingEvent($pageId, $event);
                }
            }

            // Process changes (comments, mentions)
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $this->processChangeEvent($pageId, $change);
                }
            }
        }

        Log::info('Instagram Webhook Processing Complete');

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Process messaging events (DMs)
     */
    private function processMessagingEvent($pageId, $event)
    {
        Log::info('Instagram Processing Messaging Event', [
            'page_id' => $pageId,
            'event_keys' => array_keys($event)
        ]);

        // Direct message
        if (isset($event['message'])) {
            Log::info('Instagram Dispatching Message Job', [
                'page_id' => $pageId,
                'sender_id' => $event['sender']['id'] ?? null,
                'message_id' => $event['message']['mid'] ?? null
            ]);

            ProcessInstagramMessage::dispatch($pageId, $event);
        } elseif (isset($event['message_edit'])) {
            Log::info('Instagram Dispatching ProcessInstagramMessageEdit', [
                'page_id' => $pageId,
                'message_id' => $event['message_edit']['mid'] ?? null
            ]);
            ProcessInstagramMessageEdit::dispatch($pageId, $event);
        }

        if (isset($event['delivery']) || isset($event['read'])) {
            // Dispatch status update
            \App\Jobs\ProcessMessagingStatusUpdate::dispatch('instagram', $event);
        }

        // Postback (button clicks, etc.)
        if (isset($event['postback'])) {
            Log::info('Instagram Postback Received', [
                'page_id' => $pageId,
                'payload' => $event['postback']['payload'] ?? null
            ]);
            // Handle postbacks if needed
        }
    }

    /**
     * Process change events (comments, mentions)
     */
    private function processChangeEvent($pageId, $change)
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        Log::info('Instagram Processing Change Event', [
            'page_id' => $pageId,
            'field' => $field,
            'value_keys' => array_keys($value)
        ]);

        // Comment added/edited
        if ($field === 'comments') {
            Log::info('Instagram Dispatching Comment Job', [
                'page_id' => $pageId,
                'comment_id' => $value['id'] ?? null,
                'media_id' => $value['media_id'] ?? null
            ]);

            ProcessInstagramComment::dispatch($pageId, $value);
        }

        // Story mention
        if ($field === 'mentions') {
            Log::info('Instagram Dispatching Mention Job', [
                'page_id' => $pageId,
                'mention_id' => $value['id'] ?? null
            ]);

            ProcessInstagramMention::dispatch($pageId, $value);
        }
    }
}
