<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\EmailSyncJob;
use App\Services\GmailService;
use App\Services\OutlookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEmailAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes
    public $tries = 3;

    protected $emailAccount;

    public function __construct(EmailAccount $emailAccount)
    {
        $this->emailAccount = $emailAccount;
    }

    public function handle(GmailService $gmailService, OutlookService $outlookService, \App\Services\ImapSyncService $imapService)
    {
        // Create sync job record
        $syncJob = EmailSyncJob::create([
            'email_account_id' => $this->emailAccount->id,
            'status' => 'pending',
        ]);

        try {
            $syncJob->markAsRunning();

            Log::info("SyncEmailAccountJob: Starting execution (Version 2.1 - IMAP Support) for account {$this->emailAccount->id}");

            // Determine service
            if ($this->emailAccount->imap_host) {
                 $service = $imapService;
                 Log::info("Using IMAP Service for account {$this->emailAccount->id}");
            } else {
                $service = $this->emailAccount->provider === 'gmail' 
                    ? $gmailService 
                    : $outlookService;
            }

            $emailsSynced = 0;
            $nextPageToken = null;

            $channel = \App\Models\Channel::where('tenant_id', $this->emailAccount->tenant_id)
                ->where(function($q) {
                     $q->where('config->email_account_id', $this->emailAccount->id)
                       ->orWhere('config->email_account_id', (string)$this->emailAccount->id);
                })
                ->first();

            if (!$channel) {
                Log::warning("SyncEmailAccountJob: No channel found for account {$this->emailAccount->id} Tenant: {$this->emailAccount->tenant_id}");
            } else {
                Log::info("SyncEmailAccountJob: Found channel {$channel->id} for account {$this->emailAccount->id}");
            }

            do {
                // Fetch emails
                $result = $service->fetchEmails($this->emailAccount, 50, $nextPageToken);

                // Store emails
                foreach ($result['emails'] as $emailData) {
                    $isWarmup = \App\Models\WarmupLog::where('message_id', $emailData['message_id'])->exists();
                    
                    if ($isWarmup) {
                        $emailData['is_warmup'] = true;
                    }

                    $email = Email::updateOrCreate(
                        [
                            'email_account_id' => $this->emailAccount->id,
                            'message_id' => $emailData['message_id'],
                        ],
                        $emailData
                    );

                    if ($isWarmup) {
                        // Dispatch automated usage (Read, Star, Reply)
                        \App\Jobs\ProcessIncomingWarmupEmailJob::dispatch($email);
                        Log::info("Detected incoming warmup email {$email->message_id} for account {$this->emailAccount->email_address}");
                    }
                    
                    if ($channel && !$email->is_converted && !$email->is_warmup) {
                        try {
                            $this->processEmailIntoConversation($email, $channel);
                            $email->update(['is_converted' => true]);
                        } catch (\Exception $e) {
                            Log::error("Failed to process email {$email->id} into conversation: " . $e->getMessage());
                        }
                    }
                    
                    $emailsSynced++;
                }

                $nextPageToken = $result['nextPageToken'] ?? $result['nextLink'] ?? null;

                // Limit to prevent infinite loops but allow deeper syncs
                if ($emailsSynced >= 2000) {
                    break;
                }

            } while ($nextPageToken);

            // Mark account as synced
            $this->emailAccount->markAsSynced();
            $this->emailAccount->markAsActive();

            $syncJob->markAsCompleted($emailsSynced);

            Log::info("Synced {$emailsSynced} emails for account: {$this->emailAccount->email_address}");

        } catch (\Exception $e) {
            $syncJob->markAsFailed($e->getMessage());
            
            // Critical: Only disable account if it's an Authentication Error
            // Google/Outlook return specific messages for token issues
            $msg = strtolower($e->getMessage());
            $isAuthError = str_contains($msg, 'invalid_grant') 
                        || str_contains($msg, 'unauthorized') 
                        || str_contains($msg, 'invalid_token')
                        || str_contains($msg, 'token expired')
                        || str_contains($msg, 'authentication failed')
                        || str_contains($msg, 'invalid token format');

            if ($isAuthError) {
                $this->emailAccount->markAsError($e->getMessage());
                Log::error("Email Account {$this->emailAccount->id} disabled due to Auth Error: " . $e->getMessage());
            } else {
                // For transient errors, just log. Scheduler will retry since status remains 'active'.
                Log::warning("Email sync transient failure for {$this->emailAccount->email_address}: " . $e->getMessage());
            }

            throw $e;
        }
    }

    protected $contactCache = [];

    protected function processEmailIntoConversation(Email $email, $channel)
    {
        // Determine contact email and name based on direction
        $contactEmail = null;
        $contactName = null;

        if ($email->direction === 'inbound') {
            $contactEmail = $email->from_address;
            $contactName = $email->from_name;
        } else {
            // For outbound, we use the first TO address as the contact
            $to = $email->to_addresses[0] ?? null;
            if (is_array($to)) {
                $contactEmail = $to['address'] ?? $to['email'] ?? null;
                $contactName = $to['name'] ?? null;
            } else {
                $contactEmail = $to; 
                $contactName = null;
            }
        }

        if (!$contactEmail) {
            return;
        }

        $contactEmail = strtolower(trim($contactEmail));

        // Check local cache
        if (isset($this->contactCache[$contactEmail])) {
            $contact = $this->contactCache[$contactEmail];
        } else {
            // Find or create contact
            // We iterate or improve search. For JSON in MySQL/Postgres we can use JSON functions, 
            // but simple string search (LIKE) needs care.
            // Better strategy:
            // 1. Try generic search if system supports it.
            // 2. Here: use a raw LIKE query that is robust enough or fetch potential matches.
            
            $contact = \App\Models\Contact::where('tenant_id', $channel->tenant_id)
                ->where(function ($query) use ($contactEmail) {
                    // Check if the email string exists inside the JSON content
                    // We use generic LIKE because the exact key structure might vary ('value', 'email', 'address')
                    $query->whereRaw('LOWER(emails) LIKE ?', ['%' . $contactEmail . '%']);
                })
                ->first();

            if (!$contact) {
                $contact = \App\Models\Contact::create([
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'external_id' => (string) \Illuminate\Support\Str::uuid(),
                    'name' => $contactName ?? explode('@', $contactEmail)[0],
                    'first_name' => $contactName ? explode(' ', $contactName)[0] : null,
                    'last_name' => $contactName ? (explode(' ', $contactName)[1] ?? null) : null,
                    'emails' => [['value' => $contactEmail, 'type' => 'personal']],
                    'source' => 'email_sync'
                ]);
            }
            
            // Store in cache
            $this->contactCache[$contactEmail] = $contact;
        }

        $messageAt = $email->received_at ?? $email->sent_at ?? $email->created_at;

        // Find or create conversation
        $conversation = \App\Models\Conversation::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'contact_id' => $contact->id,
            ],
            [
                'tenant_id' => $channel->tenant_id,
                'status' => 'open',
                'created_at' => $messageAt, // Use actual email date
                'updated_at' => $messageAt,
                'last_message_at' => $messageAt,
            ]
        );

        // Check if message already exists (deduplication)
        // We use message_id mapping to meta via string search or verifying connection
        // Or simply check if a message with this meta exists
        $exists = \App\Models\Message::where('conversation_id', $conversation->id)
            ->where('meta->email_message_id', $email->message_id)
            ->exists();

        if (!$exists) {
            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'direction' => $email->direction === 'inbound' ? 'inbound' : 'outbound',
                'type' => 'text', // Using text as base
                'content' => $email->body_text ?? strip_tags($email->body_html) ?? '[Empty Body]',
                'meta' => [
                    'email_id' => $email->id,
                    'email_message_id' => $email->message_id,
                    'subject' => $email->subject,
                    'is_html' => !empty($email->body_html)
                ],
                'created_at' => $messageAt, // Preserve actual time
                'updated_at' => $messageAt,
            ]);

            // Update conversation timestamp if this message is newer
            // Use last_message_at comparison for accuracy
            if (!$conversation->last_message_at || $messageAt > $conversation->last_message_at) {
                $conversation->update([
                    'updated_at' => $messageAt,
                    'last_message_at' => $messageAt,
                ]);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Email sync job failed permanently: " . $exception->getMessage());
    }
}
