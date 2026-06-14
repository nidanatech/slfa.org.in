<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\EmailAttachment;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.gmail.client_id'));
        $this->client->setClientSecret(config('services.gmail.client_secret'));
        $this->client->setRedirectUri(config('services.gmail.redirect_uri'));
        $this->client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_SEND,
            Gmail::GMAIL_MODIFY,
            Oauth2::USERINFO_EMAIL,
            Oauth2::USERINFO_PROFILE,
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens
     */
    public function authenticate($code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \Exception('Failed to authenticate: ' . $token['error']);
        }

        return $token;
    }

    /**
     * Get User Profile from Token
     */
    public function getUserProfile($token)
    {
        Log::info('GmailService::getUserProfile called', [
            'has_access_token' => isset($token['access_token']),
            'token_keys' => array_keys($token)
        ]);
        
        $this->client->setAccessToken($token);
        
        // Verify token was set
        $currentToken = $this->client->getAccessToken();
        Log::info('Token set on client', [
            'current_token_keys' => $currentToken ? array_keys($currentToken) : null,
            'is_expired' => $this->client->isAccessTokenExpired()
        ]);
        
        if ($this->client->isAccessTokenExpired() && isset($token['refresh_token'])) {
            Log::info('Token expired, refreshing...');
            $token = $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            Log::info('Token refreshed', ['new_token_keys' => array_keys($token)]);
        }

        try {
            $oauth2 = new Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'id' => $userInfo->id,
                'email' => $userInfo->email,
                'name' => $userInfo->name,
                'picture' => $userInfo->picture
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user profile', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Refresh access token
     */
    /**
     * Refresh access token
     */
    public function refreshToken($refreshToken)
    {
        // Don't set the token on the client first, just fetch new ones.
        // setAccessToken with just refresh_token might cause issues if not robust.
        
        try {
             $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        } catch (\InvalidArgumentException $e) {
             // This happens when Google returns an error (like invalid_grant) 
             // and the client library fails to parse it as a valid token array.
             throw new \Exception('Refresh failed: Provider rejected the refresh token (Invalid Grant).');
        }
        
        if (isset($newToken['error'])) {
            throw new \Exception('Failed to refresh token: ' . $newToken['error']);
        }
        
        // Ensure the new token has the refresh token preserved if not returned
        if (!isset($newToken['refresh_token'])) {
            $newToken['refresh_token'] = $refreshToken;
        }

        return $newToken;
    }

    /**
     * Fetch emails from Gmail
     */
    public function fetchEmails(EmailAccount $emailAccount, $maxResults = 50, $pageToken = null)
    {
        $this->setAccessToken($emailAccount);
        
        $gmail = new Gmail($this->client);
        $params = [
            'maxResults' => $maxResults,
            'labelIds' => ['INBOX'],
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        if ($emailAccount->sync_from_date) {
            $params['q'] = 'after:' . $emailAccount->sync_from_date->format('Y/m/d');
        }

        $messages = $gmail->users_messages->listUsersMessages('me', $params);
        $emails = [];

        foreach ($messages->getMessages() as $message) {
            $fullMessage = $gmail->users_messages->get('me', $message->getId(), ['format' => 'full']);
            $emails[] = $this->parseGmailMessage($fullMessage, $emailAccount);
        }

        return [
            'emails' => $emails,
            'nextPageToken' => $messages->getNextPageToken(),
        ];
    }

    /**
     * Send email via Gmail
     */
    public function sendEmail(EmailAccount $emailAccount, array $emailData)
    {
        $this->setAccessToken($emailAccount);
        
        $gmail = new Gmail($this->client);
        
        $message = $this->createMimeMessage($emailData);
        $gmailMessage = new Message();
        $gmailMessage->setRaw($this->base64UrlEncode($message));

        $sentMessage = $gmail->users_messages->send('me', $gmailMessage);
        
        return $this->parseGmailMessage(
            $gmail->users_messages->get('me', $sentMessage->getId()),
            $emailAccount
        );
    }

    /**
     * Mark email as read
     */
    public function markAsRead(EmailAccount $emailAccount, $messageId)
    {
        $this->setAccessToken($emailAccount);
        
        $gmail = new Gmail($this->client);
        $mods = new \Google\Service\Gmail\ModifyMessageRequest();
        $mods->setRemoveLabelIds(['UNREAD']);
        
        $gmail->users_messages->modify('me', $messageId, $mods);
    }

    /**
     * Set access token for client
     */
    protected function setAccessToken(EmailAccount $emailAccount)
    {
        if ($emailAccount->isTokenExpired() || !$emailAccount->access_token) {
            if ($emailAccount->refresh_token) {
                try {
                    $newToken = $this->refreshToken($emailAccount->refresh_token);
                    $emailAccount->update([
                        'access_token' => $newToken['access_token'],
                        'token_expires_at' => now()->addSeconds($newToken['expires_in']),
                    ]);
                    // Reload to ensure properties are fresh
                    $emailAccount->refresh();
                } catch (\Exception $e) {
                     // If refresh fails, THEN we are truly stuck
                     throw new \Exception('Authentication failed: Unable to refresh token. ' . $e->getMessage());
                }
            } else {
                 if (!$emailAccount->access_token) {
                     throw new \Exception('Authentication failed: Missing access token and no refresh token available');
                 }
            }
        }

        $tokenArray = [
            'access_token' => $emailAccount->access_token,
            'refresh_token' => $emailAccount->refresh_token,
            'expires_in' => $emailAccount->token_expires_at ? $emailAccount->token_expires_at->diffInSeconds(now()) : 3600,
            'created' => $emailAccount->token_expires_at ? $emailAccount->token_expires_at->subSeconds(3600)->timestamp : time(),
        ];

        $this->client->setAccessToken($tokenArray);
    }

    /**
     * Parse Gmail message to array
     */
    protected function parseGmailMessage($message, EmailAccount $emailAccount)
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }

        $body = $this->getMessageBody($message->getPayload());

        return [
            'email_account_id' => $emailAccount->id,
            'tenant_id' => $emailAccount->tenant_id,
            'message_id' => $message->getId(),
            'thread_id' => $message->getThreadId(),
            'subject' => $headers['Subject'] ?? '(No Subject)',
            'from_address' => $this->extractEmail($headers['From'] ?? ''),
            'from_name' => $this->extractName($headers['From'] ?? ''),
            'to_addresses' => $this->parseAddresses($headers['To'] ?? ''),
            'cc_addresses' => $this->parseAddresses($headers['Cc'] ?? ''),
            'reply_to' => $headers['Reply-To'] ?? null,
            'body_text' => $body['text'] ?? null,
            'body_html' => $body['html'] ?? null,
            'snippet' => $message->getSnippet(),
            'direction' => in_array('SENT', $message->getLabelIds() ?? []) ? 'outbound' : 'inbound',
            'is_read' => !in_array('UNREAD', $message->getLabelIds() ?? []),
            'is_starred' => in_array('STARRED', $message->getLabelIds() ?? []),
            'labels' => $message->getLabelIds(),
            'received_at' => now()->setTimestamp($message->getInternalDate() / 1000),
        ];
    }

    /**
     * Get message body from payload
     */
    protected function getMessageBody($payload)
    {
        $body = ['text' => null, 'html' => null];

        if ($payload->getBody()->getData()) {
            $mimeType = $payload->getMimeType();
            $data = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
            
            if ($mimeType === 'text/plain') {
                $body['text'] = $data;
            } elseif ($mimeType === 'text/html') {
                $body['html'] = $data;
            }
        }

        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain' && $part->getBody()->getData()) {
                    $body['text'] = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                } elseif ($part->getMimeType() === 'text/html' && $part->getBody()->getData()) {
                    $body['html'] = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                }
            }
        }

        return $body;
    }

    /**
     * Create MIME message for sending
     */
    protected function createMimeMessage(array $data)
    {
        $boundary = uniqid('boundary_');
        $message = "From: {$data['from']}\r\n";
        $message .= "To: {$data['to']}\r\n";
        if (!empty($data['cc'])) {
            $message .= "Cc: {$data['cc']}\r\n";
        }
        $message .= "Subject: {$data['subject']}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        
        // Text part
        if (!empty($data['body_text'])) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= $data['body_text'] . "\r\n\r\n";
        }
        
        // HTML part
        if (!empty($data['body_html'])) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $data['body_html'] . "\r\n\r\n";
        }
        
        $message .= "--{$boundary}--";
        
        return $message;
    }

    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function extractEmail($address)
    {
        if (preg_match('/<(.+?)>/', $address, $matches)) {
            return $matches[1];
        }
        return $address;
    }

    protected function extractName($address)
    {
        if (preg_match('/^(.+?)\s*</', $address, $matches)) {
            return trim($matches[1], '"');
        }
        return null;
    }

    protected function parseAddresses($addresses)
    {
        if (empty($addresses)) {
            return [];
        }
        
        return array_map(function($addr) {
            return $this->extractEmail(trim($addr));
        }, explode(',', $addresses));
    }
}
