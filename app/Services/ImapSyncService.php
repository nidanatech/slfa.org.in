<?php

namespace App\Services;

use App\Models\EmailAccount;
use Webklex\PHPIMAP\ClientManager;
use Illuminate\Support\Facades\Log;

class ImapSyncService
{
    public function fetchEmails(EmailAccount $account, int $limit = 50, $pageToken = null)
    {
        Log::info("IMAP Sync: Connecting to {$account->imap_host} as {$account->imap_username}");

        try {
            $client = (new ClientManager())->make([
                'host'          => $account->imap_host,
                'port'          => $account->imap_port,
                'encryption'    => $account->imap_encryption, // ssl, tls, null
                'validate_cert' => true,
                'username'      => $account->imap_username,
                'password'      => $account->imap_password, // Decrypted by model cast
                'protocol'      => 'imap',
            ]);

            $client->connect();

            $folder = $client->getFolder('INBOX');

            // Search strategy:
            // If pageToken represents an offset/UID, use it.
            // But efficiently: get messages SINCE last sync.
            
            $query = $folder->query();
            
            if ($account->last_synced_at) {
                $query->since($account->last_synced_at->subHours(24)); // Buffer overlap
            } else {
                // Initial sync: Last 30 days
                $query->since(now()->subDays(30));
            }

            // Get messages
            // Pagination in IMAP is complex. We'll just fetch 'limit' 
            // generic 'paginate' might not work well with 'since'.
            // Let's get 'unseen' or just all since date.
            
            // For simplicity in this job structure:
            // We fetch specific UIDs or just the latest batch.
            // The job logic re-checks existence, so duplicates are handled but inefficient.
            
            $messages = $query->limit($limit)->get(); 
            // Note: webklex limit might be just taking top N. 
            // We need to ensure we don't get stuck on the same N.
            // But since we are saving them, maybe we check if they exist in DB?
            
            // Better approach for IMAP:
            // Fetch UIDs of messages since date.
            // Filter out UIDs we already have (optional optimization).
            // Return content.

            $mappedEmails = [];

            /** @var \Webklex\PHPIMAP\Message $message */
            foreach ($messages as $message) {
                // Extract body
                $bodyHtml = $message->getHTMLBody();
                $bodyText = $message->getTextBody();
                
                $from = $message->getFrom()->first();
                $to = $message->getTo(); // Collection

                $toAddresses = [];
                $to->each(function($t) use (&$toAddresses) {
                    $toAddresses[] = [
                        'name' => $t->personal,
                        'email' => $t->mail,
                        'address' => $t->mail 
                    ];
                });

                $mappedEmails[] = [
                    'message_id' => $message->getMessageId(), // Global unique ID
                    'direction' => 'inbound', // Assuming we are syncing Inbox
                    'subject' => $message->getSubject(),
                    'from_name' => $from->personal ?? '',
                    'from_address' => $from->mail,
                    'to_addresses' => $toAddresses,
                    'body_html' => $bodyHtml,
                    'body_text' => $bodyText,
                    'received_at' => $message->getDate(),
                    'sent_at' => $message->getDate(), 
                    'snippet' => substr($bodyText ?? strip_tags($bodyHtml), 0, 200),
                ];
            }

            // If we got fewer than limit, we are done.
            // If we validly got 'limit', return a dummy pageToken to continue?
            // Actually, querying "SINCE" usually returns the same list unless we track verified UIDs.
            // To prevent infinite loops in the Job, we should return null for nextPageToken 
            // and rely on the next scheduled run, OR handle specific fetch ranges.
            // Imap libraries usually return all matching query.
            // So we default to "One Page" per job run for safety now.
            
            return [
                'emails' => $mappedEmails,
                'nextPageToken' => null, // Stop looping for now
            ];

        } catch (\Exception $e) {
            Log::error("IMAP Connection Failed: " . $e->getMessage());
            throw $e;
        }
    }
}
