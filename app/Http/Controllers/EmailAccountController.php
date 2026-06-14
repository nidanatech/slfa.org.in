<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Services\GmailService;
use App\Services\OutlookService;
use App\Services\EmailWarmupService;
use App\Jobs\SyncEmailAccountJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailAccountController extends Controller
{
    protected $gmailService;
    protected $outlookService;
    protected $warmupService;

    public function __construct(
        GmailService $gmailService,
        OutlookService $outlookService,
        EmailWarmupService $warmupService
    ) {
        $this->gmailService = $gmailService;
        $this->outlookService = $outlookService;
        $this->warmupService = $warmupService;
    }

    /**
     * List all connected email accounts
     */
    public function index()
    {
        $accounts = EmailAccount::forUser(auth()->id())
            ->with(['user'])
            ->get()
            ->map(function($account) {
                return [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'email_address' => $account->email_address,
                    'display_name' => $account->display_name,
                    'status' => $account->status,
                    'sync_enabled' => $account->sync_enabled,
                    'last_synced_at' => $account->last_synced_at,
                    'warmup_stats' => $this->warmupService->getWarmupStats($account),
                    'created_at' => $account->created_at,
                ];
            });

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Initiate OAuth connection for Gmail or Outlook
     */
    public function connect($provider)
    {
        if (!in_array($provider, ['gmail', 'outlook'])) {
            return response()->json(['error' => 'Invalid provider'], 400);
        }

        $authUrl = $provider === 'gmail' 
            ? $this->gmailService->getAuthUrl()
            : $this->outlookService->getAuthUrl();

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request, $provider)
    {
        if (!in_array($provider, ['gmail', 'outlook'])) {
            return redirect()->route('channels.index')->with('error', 'Invalid provider');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('channels.index')->with('error', 'Authorization failed');
        }

        try {
            $service = $provider === 'gmail' ? $this->gmailService : $this->outlookService;
            $token = $service->authenticate($code);
            
            \Log::info('OAuth Token Response', [
                'provider' => $provider,
                'has_access_token' => isset($token['access_token']),
                'has_refresh_token' => isset($token['refresh_token']),
                'expires_in' => $token['expires_in'] ?? 'N/A',
                'keys' => array_keys($token)
            ]);

            if (empty($token['access_token'])) {
                throw new \Exception('Access token missing from OAuth response');
            }

            // Get user profile
            if ($provider === 'gmail') {
                $profile = $this->gmailService->getUserProfile($token);
            } else {
                $profile = $this->getOutlookProfile($token['access_token']);
            }

            // Create or update email account
            $emailAccount = EmailAccount::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'email_address' => $profile['email'],
                ],
                [
                    'tenant_id' => auth()->user()->tenant_id,
                    'provider' => $provider,
                    'display_name' => $profile['name'],
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'token_expires_at' => isset($token['expires_in']) 
                        ? now()->addSeconds($token['expires_in']) 
                        : null,
                    'provider_account_id' => $profile['id'] ?? null,
                    'status' => 'active',
                    'sync_enabled' => true,
                    'sync_from_date' => now()->subDays(30), // Sync last 30 days
                ]
            );

            // Create corresponding Channel entry (using Service to restore if deleted)
            try {
                \Log::info('Attempting to create/update Channel for Email Account', [
                    'tenant_id' => auth()->user()->tenant_id,
                    'email' => $profile['email'],
                    'provider' => $provider
                ]);

                $config = [
                    'email_account_id' => $emailAccount->id,
                    'email' => $profile['email'],
                    'provider' => $provider
                ];

                $channelService = new \App\Services\ChannelService();
                
                $channel = $channelService->registerConnection(
                    auth()->user()->tenant_id,
                    $provider,
                    ucfirst($provider) . ' - ' . $profile['email'],
                    $config,
                    'email'
                );
                
                \Log::info('Channel created/restored successfully', ['channel_id' => $channel->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to create/update Channel for Email Account: ' . $e->getMessage());
            }

            // Check if warmup is recommended
            $warmupRecommended = $this->warmupService->isWarmupRecommended($emailAccount);

            // Dispatch initial sync job
            SyncEmailAccountJob::dispatch($emailAccount);

            return redirect()->route('channels.index')->with([
                'success' => 'Email account connected successfully',
                'warmup_recommended' => $warmupRecommended,
                'account_id' => $emailAccount->id,
            ]);

        } catch (\Exception $e) {
            $errorMessage = 'Failed to connect email account';
            
            if ($e instanceof \GuzzleHttp\Exception\ClientException) {
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $responseJson = json_decode($responseBodyAsString, true);
                if (isset($responseJson['error']['message'])) {
                    $errorMessage = 'Google Error: ' . $responseJson['error']['message'];
                }
            } else {
                $errorMessage .= ': ' . $e->getMessage();
            }

            \Log::error('Email account connection failed: ' . $e->getMessage());
            return redirect()->route('channels.index')->with('error', $errorMessage);
        }
    }

    /**
     * Disconnect email account
     */
    public function disconnect($accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);
        
        $account->update(['status' => 'disconnected']);

        return response()->json(['success' => true]);
    }

    /**
     * Trigger manual sync
     */
    public function sync($accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);

        if ($account->status !== 'active') {
            return response()->json(['error' => 'Account is not active'], 400);
        }

        SyncEmailAccountJob::dispatch($account);

        return response()->json(['success' => true, 'message' => 'Sync started']);
    }

    /**
     * Enable warmup for account
     */
    public function enableWarmup($accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);

        // Enforce Minimum Pool Size
        $activeCount = EmailAccount::where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->count();

        if ($activeCount < 2) {
             return response()->json([
                 'success' => false,
                 'message' => 'You need at least 2 active email accounts to enable warmup.'
             ], 400);
        }

        $this->warmupService->enableWarmup($account);

        return response()->json([
            'success' => true,
            'message' => 'Email warmup enabled',
            'warmup_stats' => $this->warmupService->getWarmupStats($account),
        ]);
    }

    /**
     * Skip warmup for account
     */
    public function skipWarmup($accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);

        $this->warmupService->skipWarmup($account);

        return redirect()->back()->with('success', 'Warmup skipped');
    }

    /**
     * Update email account settings (e.g., IMAP)
     */
    public function update(Request $request, $accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);

        $validated = $request->validate([
            'imap_host' => 'nullable|string',
            'imap_port' => 'nullable|integer',
            'imap_encryption' => 'nullable|string|in:ssl,tls,starttls,none',
            'imap_username' => 'nullable|string',
            'imap_password' => 'nullable|string',
            'sync_enabled' => 'boolean',
        ]);

        $account->update($validated);

        if ($account->imap_host && $account->imap_username && $account->imap_password) {
             // If valid IMAP settings provided, ensure status is active
             $account->update(['status' => 'active']);
             // Trigger sync test?
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Get warmup statistics
     */
    public function warmupStats($accountId)
    {
        $account = EmailAccount::forUser(auth()->id())->findOrFail($accountId);

        return response()->json([
            'stats' => $this->warmupService->getWarmupStats($account),
        ]);
    }

    /**
     * Helper: Get Gmail user profile
     */
    protected function getGmailProfile($accessToken)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        $data = json_decode($response->getBody(), true);

        return [
            'email' => $data['email'],
            'name' => $data['name'] ?? $data['email'],
            'id' => $data['id'],
        ];
    }

    /**
     * Helper: Get Outlook user profile
     */
    protected function getOutlookProfile($accessToken)
    {
        $graph = new \Microsoft\Graph\Graph();
        $graph->setAccessToken($accessToken);

        $user = $graph->createRequest('GET', '/me')
            ->setReturnType(\Microsoft\Graph\Model\User::class)
            ->execute();

        return [
            'email' => $user->getMail() ?? $user->getUserPrincipalName(),
            'name' => $user->getDisplayName(),
            'id' => $user->getId(),
        ];
    }
}
