<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Models\WarmupLog;
use App\Services\GmailService;
use App\Services\OutlookService;
use App\Services\SesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWarmupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $emailAccountId,
        public string $recipient
    ) {}

    public function handle()
    {
        $account = EmailAccount::find($this->emailAccountId);
        if (!$account) return;

        // Select Template
        $template = $this->getRandomTemplate($account);
        $subject = $template['subject'] . ' - ' . date('M d H:i');
        $body = $template['body'];

        try {
            $service = $this->getServiceForAccount($account);
            
            $emailData = [
                'from' => $account->email_address,
                'to' => $this->recipient,
                'subject' => $subject,
                'body_text' => $body,
                'body_html' => nl2br($body),
            ];

            // Send
            $result = $service->sendEmail($account, $emailData);

            if ($result) {
                // Log to WarmupLog
                WarmupLog::create([
                    'email_account_id' => $account->id,
                    'message_id' => $result['message_id'],
                    'recipient' => $this->recipient,
                    'sent_at' => now(),
                ]);

                // Update Account Stats (Total Sent)
                $account->increment('warmup_emails_sent');

                Log::info("Sent warmup email to {$this->recipient} from {$account->email_address}");

                // Engagement is now handled by the RECIPIENT upon processing the incoming email
                // via SyncEmailAccountJob -> ProcessIncomingWarmupEmailJob
            }

        } catch (\Exception $e) {
            Log::error("SendWarmupEmailJob Failed: " . $e->getMessage());
            // Update error rate?
        }
    }

    protected function getServiceForAccount(EmailAccount $account)
    {
        if ($account->provider === 'gmail') return app(GmailService::class);
        if ($account->provider === 'outlook') return app(OutlookService::class);
        if ($account->provider === 'ses') return app(SesService::class);
        return app(GmailService::class);
    }

    protected function getRandomTemplate(EmailAccount $account) 
    {
        try {
            $prompt = "Generate a short, natural, human-like B2B 'warmup' email (subject and body). 
            The context is a professional checking in with a colleague or business contact. 
            Topics: 'Quick question', 'Catching up', 'Project update', 'Meeting request', 'Hello'.
            Sender Name: " . ($account->name ?? 'John') . "
            Keep it very short (under 5 sentences). 
            Return ONLY valid JSON in this format: {\"subject\": \"...\", \"body\": \"...\"}";

            $system = "You are an assistant that generates natural email content for warming up email accounts. Do not include markdown formatting or backticks.";

            $response = app(\App\Services\AiService::class)->generateText($prompt, $system);
            
            // Clean response if it has backticks
            $response = str_replace(['```json', '```'], '', $response);
            $data = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($data['subject'], $data['body'])) {
                $data['body'] .= "\n\nBest,\n" . ($account->name ?? 'Team');
                return $data;
            }

        } catch (\Exception $e) {
            Log::warning("AI Warmup Template Generation Failed: " . $e->getMessage());
        }

        // Fallback to static templates
        $templates = [
            ['subject' => 'Quick question', 'body' => "Hi,\n\nI was wondering if you have time for a call?"],
            ['subject' => 'Project Update', 'body' => "Hello,\n\nJust sending a quick update on the project."],
            ['subject' => 'Meeting', 'body' => "Hi,\n\nAre we still on for the meeting?"],
            ['subject' => 'Hello', 'body' => "Hi,\n\nJust wanted to say hello."],
            ['subject' => 'Docs', 'body' => "Hi,\n\nCan you send over the documents?"],
        ];

        $template = $templates[array_rand($templates)];
        $template['body'] .= "\n\nBest,\n" . ($account->name ?? 'Team');
        
        return $template;
    }
}
