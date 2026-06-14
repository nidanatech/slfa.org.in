<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\Email;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OutlookService
{
    protected $graph;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $tenantId = 'common'; // Use 'common' for multi-tenant apps

    public function __construct()
    {
        $this->clientId = config('services.microsoft.client_id');
        $this->clientSecret = config('services.microsoft.client_secret');
        $this->redirectUri = config('services.microsoft.redirect_uri');
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl()
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email offline_access Mail.Read Mail.Send Mail.ReadWrite',
            'state' => bin2hex(random_bytes(16)),
        ];

        return 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens
     */
    public function authenticate($code)
    {
        $client = new Client();
        
        try {
            $response = $client->post('https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                    'scope' => 'Mail.Read Mail.Send Mail.ReadWrite offline_access',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['error'])) {
                throw new \Exception('Failed to authenticate: ' . $data['error_description']);
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception('OAuth authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken($refreshToken)
    {
        $client = new Client();
        
        try {
            $response = $client->post('https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                    'scope' => 'Mail.Read Mail.Send Mail.ReadWrite offline_access',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['error'])) {
                throw new \Exception('Failed to refresh token: ' . $data['error_description']);
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception('Token refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch emails from Outlook
     */
    public function fetchEmails(EmailAccount $emailAccount, $maxResults = 50, $skipToken = null)
    {
        $graph = $this->getGraphClient($emailAccount);
        
        $queryParams = [
            '$top' => $maxResults,
            '$orderby' => 'receivedDateTime DESC',
            '$select' => 'id,subject,from,toRecipients,ccRecipients,bodyPreview,body,receivedDateTime,sentDateTime,isRead,flag,hasAttachments',
        ];

        if ($skipToken) {
            $queryParams['$skiptoken'] = $skipToken;
        }

        if ($emailAccount->sync_from_date) {
            $queryParams['$filter'] = 'receivedDateTime ge ' . $emailAccount->sync_from_date->format('Y-m-d\TH:i:s\Z');
        }

        try {
            $response = $graph->createRequest('GET', '/me/messages')
                ->addHeaders(['Prefer' => 'outlook.body-content-type="text"'])
                ->setReturnType(Model\Message::class)
                ->execute();

            $emails = [];
            foreach ($response as $message) {
                $emails[] = $this->parseOutlookMessage($message, $emailAccount);
            }

            return [
                'emails' => $emails,
                'nextLink' => $response->getNextLink() ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Outlook emails: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email via Outlook
     */
    public function sendEmail(EmailAccount $emailAccount, array $emailData)
    {
        $graph = $this->getGraphClient($emailAccount);

        $message = [
            'subject' => $emailData['subject'],
            'body' => [
                'contentType' => !empty($emailData['body_html']) ? 'HTML' : 'Text',
                'content' => $emailData['body_html'] ?? $emailData['body_text'],
            ],
            'toRecipients' => $this->formatRecipients($emailData['to']),
        ];

        if (!empty($emailData['cc'])) {
            $message['ccRecipients'] = $this->formatRecipients($emailData['cc']);
        }

        if (!empty($emailData['bcc'])) {
            $message['bccRecipients'] = $this->formatRecipients($emailData['bcc']);
        }

        try {
            $response = $graph->createRequest('POST', '/me/sendMail')
                ->attachBody([
                    'message' => $message,
                    'saveToSentItems' => true,
                ])
                ->execute();

            // Fetch the sent message from Sent Items
            $sentMessages = $graph->createRequest('GET', '/me/mailFolders/SentItems/messages')
                ->addHeaders(['Prefer' => 'outlook.body-content-type="text"'])
                ->setReturnType(Model\Message::class)
                ->execute();

            if (count($sentMessages) > 0) {
                return $this->parseOutlookMessage($sentMessages[0], $emailAccount);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to send Outlook email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark email as read
     */
    public function markAsRead(EmailAccount $emailAccount, $messageId)
    {
        $graph = $this->getGraphClient($emailAccount);

        try {
            $graph->createRequest('PATCH', "/me/messages/{$messageId}")
                ->attachBody(['isRead' => true])
                ->execute();
        } catch (\Exception $e) {
            Log::error('Failed to mark Outlook email as read: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Graph client with access token
     */
    protected function getGraphClient(EmailAccount $emailAccount)
    {
        if ($emailAccount->isTokenExpired()) {
            $newToken = $this->refreshToken($emailAccount->refresh_token);
            $emailAccount->update([
                'access_token' => $newToken['access_token'],
                'token_expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);
        }

        $graph = new Graph();
        $graph->setAccessToken($emailAccount->access_token);
        
        return $graph;
    }

    /**
     * Parse Outlook message to array
     */
    protected function parseOutlookMessage($message, EmailAccount $emailAccount)
    {
        $properties = $message->getProperties();
        
        return [
            'email_account_id' => $emailAccount->id,
            'tenant_id' => $emailAccount->tenant_id,
            'message_id' => $properties['id'],
            'thread_id' => $properties['conversationId'] ?? null,
            'subject' => $properties['subject'] ?? '(No Subject)',
            'from_address' => $properties['from']['emailAddress']['address'] ?? '',
            'from_name' => $properties['from']['emailAddress']['name'] ?? null,
            'to_addresses' => $this->extractAddresses($properties['toRecipients'] ?? []),
            'cc_addresses' => $this->extractAddresses($properties['ccRecipients'] ?? []),
            'bcc_addresses' => $this->extractAddresses($properties['bccRecipients'] ?? []),
            'reply_to' => $properties['replyTo'][0]['emailAddress']['address'] ?? null,
            'body_text' => $properties['body']['contentType'] === 'text' ? $properties['body']['content'] : null,
            'body_html' => $properties['body']['contentType'] === 'html' ? $properties['body']['content'] : null,
            'snippet' => $properties['bodyPreview'] ?? null,
            'direction' => $this->determineDirection($properties, $emailAccount),
            'is_read' => $properties['isRead'] ?? false,
            'is_starred' => isset($properties['flag']) && $properties['flag']['flagStatus'] === 'flagged',
            'is_draft' => $properties['isDraft'] ?? false,
            'labels' => $properties['categories'] ?? [],
            'sent_at' => isset($properties['sentDateTime']) ? new \DateTime($properties['sentDateTime']) : null,
            'received_at' => isset($properties['receivedDateTime']) ? new \DateTime($properties['receivedDateTime']) : null,
        ];
    }

    /**
     * Format recipients for Outlook API
     */
    protected function formatRecipients($addresses)
    {
        if (is_string($addresses)) {
            $addresses = explode(',', $addresses);
        }

        return array_map(function($address) {
            $address = trim($address);
            return [
                'emailAddress' => [
                    'address' => $address,
                ],
            ];
        }, $addresses);
    }

    /**
     * Extract email addresses from recipients array
     */
    protected function extractAddresses($recipients)
    {
        return array_map(function($recipient) {
            return $recipient['emailAddress']['address'];
        }, $recipients);
    }

    /**
     * Determine email direction
     */
    protected function determineDirection($properties, EmailAccount $emailAccount)
    {
        $fromAddress = $properties['from']['emailAddress']['address'] ?? '';
        
        return strtolower($fromAddress) === strtolower($emailAccount->email_address) 
            ? 'outbound' 
            : 'inbound';
    }

    /**
     * Download attachment
     */
    public function downloadAttachment(EmailAccount $emailAccount, $messageId, $attachmentId)
    {
        $graph = $this->getGraphClient($emailAccount);

        try {
            $attachment = $graph->createRequest('GET', "/me/messages/{$messageId}/attachments/{$attachmentId}")
                ->setReturnType(Model\Attachment::class)
                ->execute();

            return $attachment;
        } catch (\Exception $e) {
            Log::error('Failed to download Outlook attachment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user profile information
     */
    public function getUserProfile(EmailAccount $emailAccount)
    {
        $graph = $this->getGraphClient($emailAccount);

        try {
            $user = $graph->createRequest('GET', '/me')
                ->setReturnType(Model\User::class)
                ->execute();

            return [
                'email' => $user->getMail() ?? $user->getUserPrincipalName(),
                'name' => $user->getDisplayName(),
                'id' => $user->getId(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Outlook user profile: ' . $e->getMessage());
            throw $e;
        }
    }
}
