<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\WarmupLog;
use Illuminate\Support\Facades\Log;

class WarmupReplyService
{
    private array $replyTemplates = [
        "Thanks for reaching out! Everything looks good on my end.",
        "Received your email. Thanks for the update.",
        "Hi, thanks for checking in. I'm doing well.",
        "Got it, appreciate the info.",
        "Thanks for the email. Let's catch up soon.",
        "Received. Have a great day!",
        "Thanks for touching base.",
        "Hi, just confirming receipt of your email.",
        "Thanks for the follow-up.",
        "Got your message, thanks!",
    ];

    /**
     * Send a threaded reply to a warmup email
     */
    public function reply(Email $email)
    {
        try {
            $account = $email->emailAccount;
            
            // Try AI Generation
            $replyText = $this->generateAiReply($email, $account);
            
            // Fallback
            if (!$replyText) {
                $replyText = $this->replyTemplates[array_rand($this->replyTemplates)];
            }

            // Prepare email data with threading headers
            $emailData = [
                'from' => $account->email_address,
                'to' => $email->from_address,
                'subject' => 'Re: ' . $email->subject,
                'body_text' => $replyText,
                'body_html' => nl2br($replyText),
                'in_reply_to' => $email->message_id, // Important for threading
                'references' => $email->message_id,   // Important for threading
            ];

            // Use the appropriate service to send
            $service = $this->getServiceForAccount($account);
            $result = $service->sendEmail($account, $emailData);

            if ($result) {
                // 1. Mark original message as replied
                WarmupLog::where('message_id', $email->message_id)
                    ->update(['replied_at' => now()]);

                // 2. Log NEW message as warmup so the other side recognizes it
                WarmupLog::create([
                    'email_account_id' => $account->id, // The replier
                    'message_id' => $result['message_id'],
                    'recipient' => $email->from_address,
                    'sent_at' => now(),
                ]);
                
                Log::info("Sent warmup reply for message {$email->message_id} -> New ID: {$result['message_id']}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to send warmup reply: " . $e->getMessage());
        }
    }

    protected function generateAiReply(Email $originalEmail, EmailAccount $account): ?string
    {
        try {
            $originalContent = $originalEmail->body_text ?? strip_tags($originalEmail->body_html ?? '');
            
            $prompt = "Generate a short, natural, professional B2B email reply to the following message.
            Sender Name: " . ($account->name ?? 'John') . "
            Original Message: \"$originalContent\"
            
            The reply should be positive, brief (1-3 sentences), and encourage further connection if appropriate, or just acknowledge receipt.
            Do NOT include subject line. Return ONLY the body text.";

            $system = "You are a busy professional replying to emails. Keep it short and human-like.";

            $response = app(\App\Services\AiService::class)->generateText($prompt, $system);
            
            if (strlen($response) > 5) {
                return trim($response);
            }
        } catch (\Exception $e) {
            Log::warning("AI Warmup Reply Generation Failed: " . $e->getMessage());
        }
        
        return null;
    }

    protected function getServiceForAccount(EmailAccount $account)
    {
        if ($account->provider === 'gmail') return app(GmailService::class);
        if ($account->provider === 'outlook') return app(OutlookService::class);
        if ($account->provider === 'ses') return app(SesService::class);
        return app(GmailService::class);
    }
}
