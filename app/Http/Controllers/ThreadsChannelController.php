<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreadsChannelController extends Controller
{
    private const GRAPH_VERSION = 'v1.0';
    private const OAUTH_URL = 'https://threads.net/oauth/authorize';
    private const TOKEN_URL = 'https://graph.threads.net/oauth/access_token';

    private const SCOPES = [
        'threads_delete',
        'threads_manage_insights',
        'threads_manage_mentions',
        'threads_manage_replies',
        'threads_profile_discovery',
        'threads_read_replies'
    ];

    /**
     * Initiate OAuth flow
     */
    public function connect()
    {
        $clientId = config('services.threads.client_id');
        $redirectUri = config('services.threads.redirect');
        $scopes = implode(',', self::SCOPES);

        $authUrl = self::OAUTH_URL . '?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            Log::error('Threads OAuth callback missing code');
            return redirect()->route('dashboard')->with('error', 'Failed to connect Threads account');
        }

        try {
            // Exchange code for access token
            $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.threads.client_id'),
                'client_secret' => config('services.threads.client_secret'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.threads.redirect'),
                'code' => $code,
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Threads token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body()
                ]);
                return redirect()->route('dashboard')->with('error', 'Failed to exchange code for token');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];
            $userId = $tokenData['user_id'];

            // Get user profile
            $profileResponse = Http::withToken($accessToken)
                ->get('https://graph.threads.net/v1.0/me', [
                    'fields' => 'id,username,name,threads_profile_picture_url,threads_biography'
                ]);

            if (!$profileResponse->successful()) {
                Log::error('Threads profile fetch failed', [
                    'status' => $profileResponse->status(),
                    'body' => $profileResponse->body()
                ]);
                return redirect()->route('dashboard')->with('error', 'Failed to fetch profile');
            }

            $profile = $profileResponse->json();

            // Check if channel already exists
            $existingChannel = Channel::where('type', 'threads')
                ->where('tenant_id', Auth::user()->tenant_id)
                ->where('config->user_id', $userId)
                ->first();

            if ($existingChannel) {
                // Update existing channel
                $existingChannel->update([
                    'name' => '@' . ($profile['username'] ?? 'Unknown'),
                    'config' => [
                        'user_id' => $userId,
                        'username' => $profile['username'] ?? null,
                        'name' => $profile['name'] ?? null,
                        'profile_picture_url' => $profile['threads_profile_picture_url'] ?? null,
                        'biography' => $profile['threads_biography'] ?? null,
                        'access_token' => $accessToken,
                    ],
                    'is_active' => true,
                ]);

                Log::info('Threads channel updated', ['channel_id' => $existingChannel->id]);

                return redirect()->route('dashboard')
                    ->with('success', 'Threads account reconnected successfully');
            }

            // Create new channel
            $channel = Channel::create([
                'tenant_id' => Auth::user()->tenant_id,
                'type' => 'threads',
                'name' => '@' . ($profile['username'] ?? 'Unknown'),
                'config' => [
                    'user_id' => $userId,
                    'username' => $profile['username'] ?? null,
                    'name' => $profile['name'] ?? null,
                    'profile_picture_url' => $profile['threads_profile_picture_url'] ?? null,
                    'biography' => $profile['threads_biography'] ?? null,
                    'access_token' => $accessToken,
                ],
                'is_active' => true,
            ]);

            Log::info('Threads channel created', [
                'channel_id' => $channel->id,
                'username' => $profile['username'] ?? null
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Threads account connected successfully');

        } catch (\Exception $e) {
            Log::error('Threads OAuth callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect Threads account: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Threads channel
     */
    public function disconnect(Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id || $channel->type !== 'threads') {
            abort(403, 'Unauthorized');
        }

        $channel->update(['is_active' => false]);
        
        Log::info('Threads channel disconnected', ['channel_id' => $channel->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Threads account disconnected successfully');
    }
}
