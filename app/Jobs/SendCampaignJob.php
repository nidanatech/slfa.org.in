<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\CampaignRecipient;
use App\Services\GmailService;
use App\Services\OutlookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public EmailCampaign $campaign
    ) {}

    public function handle(): void
    {
        try {
            // Mark campaign as sending
            $this->campaign->markAsSending();

            // Get pending recipients
            $recipients = $this->campaign->recipients()->pending()->get();

            if ($recipients->isEmpty()) {
                $this->campaign->markAsSent();
                return;
            }

            // Get email service
            $emailAccount = $this->campaign->emailAccount;
            $service = $emailAccount->provider === 'gmail' 
                ? new GmailService() 
                : new OutlookService();

            // Send to each recipient
            foreach ($recipients as $recipient) {
                try {
                    // Check Contact DND Settings
                    $contact = Contact::where('tenant_id', $this->campaign->tenant_id)
                        ->where(function($q) use ($recipient) {
                            $q->where('emails', 'like', '%"value":"' . $recipient->email . '"%')
                              ->orWhere('emails', 'like', '%"' . $recipient->email . '"%');
                        })
                        ->first();

                    if ($contact && $contact->dnd_email) {
                        $recipient->markAsFailed("DND Enabled");
                        continue;
                    }

                    // Prepare email content with personalization
                    $content = $this->personalizeContent($this->campaign->content_html, $recipient);
                    
                    // Add tracking pixel for opens
                    $content = $this->addTrackingPixel($content, $recipient);
                    
                    // Wrap links for click tracking
                    $content = $this->wrapLinksForTracking($content, $recipient);

                    // Send email
                    $service->sendEmail(
                        $emailAccount,
                        $recipient->email,
                        $this->campaign->subject,
                        $content,
                        $this->campaign->content_text
                    );

                    // Mark as sent
                    $recipient->markAsSent();
                    $this->campaign->incrementSentCount();

                    // Small delay to avoid rate limits
                    usleep(100000); // 0.1 second

                } catch (\Exception $e) {
                    Log::error('Failed to send campaign email', [
                        'campaign_id' => $this->campaign->id,
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $recipient->markAsFailed($e->getMessage());
                }
            }

            // Mark campaign as sent
            $this->campaign->markAsSent();

        } catch (\Exception $e) {
            Log::error('Campaign sending failed', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage()
            ]);
            
            $this->campaign->markAsFailed();
            throw $e;
        }
    }

    private function personalizeContent(string $content, CampaignRecipient $recipient): string
    {
        // Replace contact personalization tags
        $replacements = [
            '{{contact.name}}' => $recipient->name ?? 'there',
            '{{contact.email}}' => $recipient->email,
            '{{contact.first_name}}' => explode(' ', $recipient->name ?? '')[0] ?? 'there',
        ];

        // Add custom fields with contact. prefix
        if ($recipient->custom_fields) {
            foreach ($recipient->custom_fields as $key => $value) {
                $replacements["{{contact." . $key . "}}"] = $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    private function addTrackingPixel(string $content, CampaignRecipient $recipient): string
    {
        $trackingUrl = route('campaigns.track.open', [
            'campaign' => $this->campaign->id,
            'recipient' => $recipient->id
        ]);

        $pixel = '<img src="' . $trackingUrl . '" width="1" height="1" style="display:none;" />';
        
        // Add before closing body tag, or at the end if no body tag
        if (stripos($content, '</body>') !== false) {
            return str_ireplace('</body>', $pixel . '</body>', $content);
        }
        
        return $content . $pixel;
    }

    private function wrapLinksForTracking(string $content, CampaignRecipient $recipient): string
    {
        // Find all links and wrap them with tracking URLs
        return preg_replace_callback(
            '/<a\s+href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) use ($recipient) {
                $originalUrl = $matches[1];
                
                // Skip if it's already a tracking URL or an anchor
                if (str_starts_with($originalUrl, '#') || str_contains($originalUrl, 'campaigns.track')) {
                    return $matches[0];
                }

                $trackingUrl = route('campaigns.track.click', [
                    'campaign' => $this->campaign->id,
                    'recipient' => $recipient->id,
                    'url' => base64_encode($originalUrl)
                ]);

                return '<a href="' . $trackingUrl . '"' . $matches[2] . '>';
            },
            $content
        );
    }
}
