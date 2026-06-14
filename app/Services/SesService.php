<?php

namespace App\Services;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SesService
{
    protected $client;

    public function __construct()
    {
        // We only initialize if config is present.
        // If app is not configured, methods might fail or return false.
        $config = [
            'version' => 'latest',
            'region'  => config('services.ses.region'),
            'credentials' => [
                'key'    => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ];

        \Log::info('SesService Config Check', [
            'key_present' => !empty($config['credentials']['key']),
            'secret_present' => !empty($config['credentials']['secret']),
            'region' => $config['region'],
            'key_start' => substr($config['credentials']['key'] ?? '', 0, 4) . '...', 
        ]);

        if ($config['credentials']['key'] && $config['credentials']['secret']) {
            $this->client = new SesClient($config);
        } else {
            \Log::warning('SES Service not initialized: Missing AWS_ACCESS_KEY_ID or AWS_SECRET_ACCESS_KEY in .env');
        }
    }

    /**
     * Create/Verify an Email Identity (Simple Link)
     */
    public function verifyEmailIdentity($email)
    {
        if (!$this->client) return null;

        try {
            $result = $this->client->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);
            return true;
        } catch (AwsException $e) {
            Log::error("SES Verify Email Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create/Verify a Domain Identity with Easy DKIM
     * Returns array of DKIM tokens (CNAMEs)
     */
    public function verifyDomainIdentity($domain)
    {
        if (!$this->client) return null;

        try {
            // 1. Verify Domain Identity (Legacy/Fundamental) - Optional but good check
            // Actually, verifyDomainDkim DOES verify the identity implicitly if configured.
            // But usually we call verifyDomainIdentity to get the TXT token (fallback)
            // AND verifyDomainDkim for CNAMEs.
            
            // Step A: Request DKIM Tokens
            $dkimResult = $this->client->verifyDomainDkim([
                'Domain' => $domain,
            ]);
            
            $dkimTokens = $dkimResult['DkimTokens'];
            
            // Step B: Also get TXT verification token? (For standard verify)
            // New accounts recommended to use Easy DKIM (CNAME) which suffices for verification + auth.
            // So we primarily return DKIM tokens.
            
            return [
                'dkim_tokens' => $dkimTokens,
            ];

        } catch (AwsException $e) {
            Log::error("SES Verify Domain Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check verification status
     * Returns 'Success', 'Pending', 'Failed'
     */
    public function getIdentityStatus($identity)
    {
         if (!$this->client) return 'failed';
         
         try {
             $result = $this->client->getIdentityVerificationAttributes([
                 'Identities' => [$identity],
             ]);
             
             $attributes = $result['VerificationAttributes'];
             
             if (isset($attributes[$identity])) {
                 return $attributes[$identity]['VerificationStatus'];
             }
             
             return 'NotFound';
             
         } catch (AwsException $e) {
            Log::error("SES Check Status Error: " . $e->getMessage());
            return 'failed';
         }
    }
    
    /**
     * Get DKIM Attributes (Status + Tokens)
     */
    public function getDkimAttributes($domain)
    {
        if (!$this->client) return null;

        try {
            $result = $this->client->getIdentityDkimAttributes([
                'Identities' => [$domain],
            ]);
            
            $attributes = $result['DkimAttributes'];
            
            if (isset($attributes[$domain])) {
                return $attributes[$domain];
            }
            return null;
            
        } catch (AwsException $e) {
             Log::error("SES Check DKIM Error: " . $e->getMessage());
             return null;
        }
    }

    /**
     * Check DKIM Status specifically
     */
    public function getDkimStatus($domain)
    {
        $attrs = $this->getDkimAttributes($domain);
        return $attrs['DkimVerificationStatus'] ?? 'failed';
    }
    /**
     * Get SES Send Statistics (Bounces, Complaints, etc.)
     * Returns array of data points
     */
    public function getSendStatistics()
    {
        if (!$this->client) return [];

        try {
            $result = $this->client->getSendStatistics();
            return $result['SendDataPoints'] ?? [];
        } catch (AwsException $e) {
            Log::error("SES Get Statistics Error: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Send email via SES
     * Compatible with EmailWarmupService interface
     */
    public function sendEmail(\App\Models\EmailAccount $emailAccount, array $emailData)
    {
        if (!$this->client) {
            throw new \Exception("SES Client not initialized");
        }

        try {
            $result = $this->client->sendEmail([
                'Destination' => [
                    'ToAddresses' => is_array($emailData['to']) ? $emailData['to'] : [$emailData['to']],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $emailData['body_html'] ?? $emailData['body_text'] ?? '',
                        ],
                        'Text' => [
                            'Charset' => 'UTF-8',
                            'Data' => $emailData['body_text'] ?? strip_tags($emailData['body_html'] ?? ''),
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $emailData['subject'],
                    ],
                ],
                'Source' => $emailData['from'],
                // Add Reply-To if needed
            ]);

            $messageId = $result['MessageId'];

            // Return standardized structure for Email model creation
            return [
                'email_account_id' => $emailAccount->id,
                'tenant_id' => $emailAccount->tenant_id,
                'message_id' => $messageId,
                'subject' => $emailData['subject'],
                'from_address' => $emailData['from'],
                'from_name' => $emailAccount->name, 
                'to_addresses' => is_array($emailData['to']) ? $emailData['to'] : [$emailData['to']],
                'body_text' => $emailData['body_text'],
                'body_html' => $emailData['body_html'],
                'direction' => 'outbound',
                'is_read' => true,
                'is_starred' => false,
                'sent_at' => now(),
                // 'received_at' => ... (null for outbound)
            ];

        } catch (AwsException $e) {
            Log::error("SES Send Email Error: " . $e->getMessage());
            throw $e;
        }
    }
}
