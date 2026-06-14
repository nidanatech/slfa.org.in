<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\Pool;
use App\Models\Channel;
use Exception;

class InstagramChannelController extends Controller
{


    private const SCOPES = [
        'instagram_basic',
        'instagram_manage_messages',
        'pages_show_list',
        'pages_read_engagement',
        'business_management'
    ];

    /**
     * Redirect to Facebook for Instagram Messaging authentication
     */
    public function connect()
    {
        $clientId = config('services.facebook.client_id');
        $redirectUri = route('channels.instagram.callback');
        
        $url = 'https://www.facebook.com/v24.0/dialog/oauth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(',', self::SCOPES),
        ]);

        return redirect($url);
    }

    /**
     * Handle Instagram OAuth Callback (via Facebook)
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::error('Instagram Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'Instagram connection failed: ' . ($request->query('error_description') ?? 'Access denied'));
            }

            $code = $request->query('code');
            $clientId = config('services.facebook.client_id');
            $clientSecret = config('services.facebook.client_secret');
            $redirectUri = route('channels.instagram.callback');

            // 1. Exchange code for long-lived user access token
            $response = Http::get('https://graph.facebook.com/v24.0/oauth/access_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('Instagram Token Error', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to obtain Facebook access token.');
            }

            $userAccessToken = $data['access_token'];
            
            Log::info('Instagram Token Debug', [
                'token_prefix' => substr($userAccessToken, 0, 10) . '...',
                'token_length' => strlen($userAccessToken),
                'client_id' => $clientId
            ]);

            // 2. Fetch User's Pages to find linked Instagram Business Accounts
            $response = Http::get("https://graph.facebook.com/v24.0/me/accounts", [
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}',
                'access_token' => $userAccessToken,
            ]);

            $pagesData = $response->json()['data'] ?? [];
            $instagramAccounts = [];

            foreach ($pagesData as $page) {
                if (isset($page['instagram_business_account'])) {
                    $instagramAccounts[] = [
                        'page_id' => $page['id'],
                        'page_name' => $page['name'],
                        'page_access_token' => $page['access_token'],
                        'instagram_id' => $page['instagram_business_account']['id'],
                        'username' => $page['instagram_business_account']['username'] ?? '',
                        'name' => $page['instagram_business_account']['name'] ?? $page['name'],
                        'profile_picture_url' => $page['instagram_business_account']['profile_picture_url'] ?? null,
                    ];
                }
            }

            if (empty($instagramAccounts)) {
                return redirect()->route('channels.index')->with('error', 'No Instagram Business Accounts found linked to your Facebook Pages.');
            }

            // Try to connect all available Instagram accounts
            $tenant = Auth::user()->tenant;
            $connectedAccounts = [];
            $skippedAccounts = [];
            $errors = [];

            foreach ($instagramAccounts as $account) {
                try {
                    $config = [
                        'instagram_id' => $account['instagram_id'],
                        'page_id' => $account['page_id'],
                        'page_access_token' => $account['page_access_token'],
                        'access_token' => $account['page_access_token'], // Use Page Token for messaging
                        'page_token_prefix' => substr($account['page_access_token'], 0, 10) . '...',
                        'user_access_token' => $userAccessToken,
                        'username' => $account['username'],
                        'profile_picture_url' => $account['profile_picture_url'],
                        'connected_at' => now()->toDateTimeString(),
                    ];

                    $channelService = new \App\Services\ChannelService();
                    $channel = $channelService->registerConnection(
                        $tenant->id,
                        'instagram',
                        $account['name'],
                        $config,
                        'instagram_id'
                    );

                    if ($channel) {
                        $this->subscribe($account['instagram_id'], $account['page_access_token']);
                        $connectedAccounts[] = $account['username'];
                        Log::info("Successfully connected Instagram account", ['username' => $account['username'], 'instagram_id' => $account['instagram_id']]);
                    }
                } catch (Exception $e) {
                    // Log the specific account that failed
                    Log::warning("Failed to connect Instagram account", [
                        'username' => $account['username'],
                        'instagram_id' => $account['instagram_id'],
                        'error' => $e->getMessage()
                    ]);
                    
                    // Check if it's already connected to another tenant
                    if (str_contains($e->getMessage(), 'already connected')) {
                        $skippedAccounts[] = $account['username'];
                    } else {
                        $errors[] = "{$account['username']}: {$e->getMessage()}";
                    }
                }
            }

            // Build response message
            if (!empty($connectedAccounts)) {
                $message = "Successfully connected Instagram account(s): " . implode(', ', $connectedAccounts);
                if (!empty($skippedAccounts)) {
                    $message .= ". Skipped (already connected): " . implode(', ', $skippedAccounts);
                }
                return redirect()->route('channels.index')->with('success', $message);
            } elseif (!empty($skippedAccounts)) {
                return redirect()->route('channels.index')->with('warning', 'All Instagram accounts are already connected to other businesses: ' . implode(', ', $skippedAccounts));
            } else {
                $errorMessage = 'Failed to connect Instagram accounts.';
                if (!empty($errors)) {
                    $errorMessage .= ' Errors: ' . implode('; ', $errors);
                }
                return redirect()->route('channels.index')->with('error', $errorMessage);
            }

        } catch (Exception $e) {
            Log::error('Instagram Callback Exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('channels.index')->with('error', 'An internal error occurred during Instagram connection: ' . $e->getMessage());
        }
    }

    private function subscribe($pageId, $pageAccessToken)
    {
        $url = "https://graph.facebook.com/v24.0/{$pageId}/subscribed_apps";
        
        try {
            Http::post($url, [
                'access_token' => $pageAccessToken,
                'subscribed_fields' => 'messages', // As per user request: messages
            ]);
            Log::info("Subscribed to Instagram Webhooks for ID: {$pageId}");
        } catch (Exception $e) {
            Log::error("Webhook Subscribe Fail: {$pageId}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get Channel Overview
     */
    public function getOverview(\App\Models\Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        
        $service = new \App\Services\InstagramOverviewService();
        $data = $service->getOverviewData($channel);

        return response()->json($data);
    }
}
