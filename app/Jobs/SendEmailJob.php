<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Services\GmailService;
use App\Services\OutlookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    protected $emailAccount;
    protected $emailData;

    public function __construct(EmailAccount $emailAccount, array $emailData)
    {
        $this->emailAccount = $emailAccount;
        $this->emailData = $emailData;
    }

    public function handle(GmailService $gmailService, OutlookService $outlookService)
    {
        if ($this->emailAccount->status !== 'active') {
            throw new \Exception('Email account is not active');
        }

        try {
            $service = $this->emailAccount->provider === 'gmail' 
                ? $gmailService 
                : $outlookService;

            // Prepare email data
            $emailData = [
                'from' => $this->emailAccount->email_address,
                'to' => $this->emailData['to'],
                'cc' => $this->emailData['cc'] ?? null,
                'bcc' => $this->emailData['bcc'] ?? null,
                'subject' => $this->emailData['subject'],
                'body_text' => $this->emailData['body_text'] ?? null,
                'body_html' => $this->emailData['body_html'] ?? null,
            ];

            // Send email
            $sentEmail = $service->sendEmail($this->emailAccount, $emailData);

            // Store sent email in database
            if ($sentEmail) {
                Email::create($sentEmail);
            }

            Log::info("Email sent successfully from {$this->emailAccount->email_address} to {$emailData['to']}");

        } catch (\Exception $e) {
            Log::error("Failed to send email from {$this->emailAccount->email_address}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Send email job failed permanently: " . $exception->getMessage());
        
        // Optionally notify user about failed email
        // You could create a notification here
    }
}
