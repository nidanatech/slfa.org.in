<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\EmailAccount;
use App\Services\GmailService;
use App\Services\OutlookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(GmailService $gmailService, OutlookService $outlookService)
    {
        try {
            $this->message->load(['conversation.channel', 'conversation.contact']);
            $conversation = $this->message->conversation;
            $channel = $conversation->channel;
            $contact = $conversation->contact;

            if (!$channel || !isset($channel->config['email_account_id'])) {
                Log::error("SendEmailReplyJob: No email account configured for channel {$channel->id}");
                return;
            }

            $emailAccount = EmailAccount::find($channel->config['email_account_id']);
            if (!$emailAccount) {
                Log::error("SendEmailReplyJob: Email account not found for channel {$channel->id}");
                return;
            }

            // Determine Service
            $service = $emailAccount->provider === 'gmail' ? $gmailService : $outlookService;

            // Prepare Email Data
            // Find last inbound message for subject/threading references?
            $lastInbound = $conversation->messages()
                ->where('direction', 'inbound')
                ->whereNotNull('meta->subject')
                ->latest()
                ->first();

            $subject = $lastInbound ? $lastInbound->meta['subject'] : 'No Subject';
            if (!str_starts_with(strtolower($subject), 're:')) {
                $subject = 'Re: ' . $subject;
            }

            $emailData = [
                'from' => $emailAccount->email_address,
                'to' => $contact->primary_email, // Accessor from Contact model
                'subject' => $subject,
                'body_text' => strip_tags($this->message->content),
                'body_html' => nl2br($this->message->content), // Simple conversion
                // 'cc' => ...
            ];

            if (empty($emailData['to'])) {
                Log::error("SendEmailReplyJob: No primary email for contact {$contact->id}");
                return;
            }

            // Send
            $sentMessage = $service->sendEmail($emailAccount, $emailData);

            // Update local message with external ID
            if ($sentMessage && isset($sentMessage['message_id'])) {
                $this->message->update([
                    'meta' => array_merge($this->message->meta ?? [], [
                        'email_message_id' => $sentMessage['message_id'],
                        'email_thread_id' => $sentMessage['thread_id'] ?? null,
                        'sent_via' => $emailAccount->provider
                    ])
                ]);
            }

            Log::info("SendEmailReplyJob: Reply sent for message {$this->message->id} via {$emailAccount->provider}");

        } catch (\Exception $e) {
            Log::error("SendEmailReplyJob: Failed to send reply: " . $e->getMessage());
            // Optionally release back to queue
            $this->fail($e);
        }
    }
}
