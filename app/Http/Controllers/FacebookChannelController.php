<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\Pool;
use App\Models\Channel;
use Exception;

class FacebookChannelController extends Controller
{
    private const GRAPH_VERSION = 'v24.0';

    private const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_read_user_content',
        'pages_manage_metadata',
        'pages_messaging',
        'business_management',
    ];

    private const WEBHOOK_FIELDS = [
        'messages',
        'messaging_postbacks',
        'messaging_optins',
        'messaging_referrals',
        'message_deliveries',
        'message_reads'
    ];

    /**
     * Redirect to Facebook for authentication
     */
    public function connect()
    {
        $redirectUri = config('app.url') . '/channels/facebook/callback';
        
        $params = [
            'client_id' => config('services.facebook.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(',', self::SCOPES),
        ];

        Log::info('Facebook Connect: Starting OAuth', [
            'client_id' => $params['client_id'],
            'redirect_uri' => $params['redirect_uri']
        ]);

        $url = 'https://www.facebook.com/' . self::GRAPH_VERSION . '/dialog/oauth?' . http_build_query($params);

        return redirect($url);
    }

    /**
     * Handle Facebook OAuth Callback
     */
    public function callback(Request $request)
    {
        try {
            // Handle error or user denial
            if ($request->has('error')) {
                Log::error('Facebook Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'Facebook connection failed: ' . ($request->query('error_reason') ?? 'Unknown error'));
            }

            $shortLivedCode = $request->query('code');
            $redirectUri = config('app.url') . '/channels/facebook/callback';

            // Exchange code for long-lived access token
            $response = Http::asForm()->post('https://graph.facebook.com/' . self::GRAPH_VERSION . '/oauth/access_token', [
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'code' => $shortLivedCode,
                'redirect_uri' => $redirectUri,
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('Error exchanging token', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to exchange token with Facebook.');
            }

            $longLivedAccessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? null;

            // Fetch Pages (optimized with concurrent requests)
            $pages = $this->fetchPages($longLivedAccessToken);

            if (empty($pages)) {
                return redirect()->route('channels.index')->with('error', 'No Facebook Pages found for this account.');
            }

            $tenant = Auth::user()->tenant;
            if (!$tenant) {
                return redirect()->route('channels.index')->with('error', 'No tenant associated with user.');
            }

            $connectedCount = 0;
            $skippedCount = 0;
            
            // Channel Service for restoration logic
            $channelService = new \App\Services\ChannelService();

            foreach ($pages as $page) {
                try {
                   $pageId = (string) $page['id'];
                    
                    $config = [
                        'page_id' => $pageId,
                        'profile_picture_url' => $page['picture']['data']['url'] ?? null,
                        'access_token' => $page['access_token'],
                        'user_access_token' => $longLivedAccessToken,
                        'expires_in' => $expiresIn,
                        'connected_at' => now()->toDateTimeString(),
                    ];

                    // Use Service to Create or Restore
                    $channel = $channelService->registerConnection(
                        $tenant->id,
                        'messenger',
                        $page['name'],
                        $config,
                        'page_id'
                    );

                    if ($channel) {
                        // Subscribe to Webhook
                        $this->subscribe($pageId, $page['access_token']);
                        $connectedCount++;
                    } 
                } catch (Exception $e) {
                    Log::warning("Skipping Facebook Page '{$page['name']}': " . $e->getMessage());
                    $skippedCount++;
                }
            }

            if ($connectedCount > 0) {
                $msg = "Success! Connected/Restored {$connectedCount} Facebook Page(s).";
                if ($skippedCount > 0) {
                    $msg .= " Note: {$skippedCount} pages were skipped as they are already connected elsewhere.";
                }
                return redirect()->route('channels.index')->with('success', $msg);
            } elseif ($skippedCount > 0) {
                 return redirect()->route('channels.index')->with('info', "Facebook Pages synced. {$skippedCount} pages were found but they are already connected to other accounts.");
            } else {
                return redirect()->route('channels.index')->with('info', 'Facebook Pages synced. No new pages were added.');
            }

        } catch (Exception $e) {
            Log::error('Facebook Channel Callback Exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('channels.index')->with('error', 'An unexpected error occurred during Facebook connection.');
        }
    }

    /**
     * Fetch all pages (personal + business) with concurrency
     */
    private function fetchPages($accessToken)
    {
        $graphUrl = 'https://graph.facebook.com/' . self::GRAPH_VERSION;
        $fields = 'id,name,picture{url},access_token';

        // 1. Fetch user-managed pages
        $userPagesResponse = Http::get("$graphUrl/me/accounts", [
            'access_token' => $accessToken,
            'fields' => $fields,
        ]);
        
        $pagesData = $userPagesResponse->json()['data'] ?? [];

        // 2. If no direct pages, check business pages
        if (empty($pagesData)) {
            Log::info('No direct pages found, checking businesses...');
            
            $bizResponse = Http::get("$graphUrl/me/businesses", [
                'access_token' => $accessToken,
            ]);
            
            $businesses = $bizResponse->json()['data'] ?? [];
            
            if (!empty($businesses)) {
                // Prepare requests for all businesses and types
                $requests = [];
                foreach ($businesses as $biz) {
                    foreach (['owned_pages', 'client_pages', 'managed_pages'] as $type) {
                        $requests[] = [
                            'url' => "$graphUrl/{$biz['id']}/$type",
                            'params' => [
                                'access_token' => $accessToken,
                                'fields' => $fields
                            ]
                        ];
                    }
                }

                // Execute concurrent requests
                $responses = Http::pool(fn (Pool $pool) => 
                    array_map(fn ($req) => $pool->get($req['url'], $req['params']), $requests)
                );

                // Collect results
                foreach ($responses as $response) {
                    if ($response->ok()) {
                        $data = $response->json()['data'] ?? [];
                        if (!empty($data)) {
                            $pagesData = array_merge($pagesData, $data);
                        }
                    } else {
                        Log::warning('Failed to fetch business pages', ['status' => $response->status(), 'body' => $response->body()]);
                    }
                }
            }
        }

        return $pagesData;
    }

    /**
     * Subscribe to Webhook
     */
    private function subscribe($pageId, $pageAccessToken)
    {
        $url = 'https://graph.facebook.com/' . self::GRAPH_VERSION . "/{$pageId}/subscribed_apps";

        try {
            $response = Http::post($url, [
                'access_token' => $pageAccessToken,
                'subscribed_fields' => self::WEBHOOK_FIELDS,
            ]);

            $data = $response->json();

            if (isset($data['success']) && $data['success'] === true) {
                Log::info("Webhook subscribed for Page ID: {$pageId}");
                return true;
            } else {
                Log::warning("Failed to subscribe webhook for Page ID: {$pageId}", ['response' => $data]);
                return false;
            }
        } catch (Exception $e) {
            Log::error("Exception subscribing webhook for Page ID: {$pageId}", ['error' => $e->getMessage()]);
            return false;
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
        
        $service = new \App\Services\FacebookOverviewService();
        $data = $service->getOverviewData($channel);

        return response()->json($data);
    }
}
