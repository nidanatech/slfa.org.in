<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class TikTokChannelController extends Controller
{
    private const AUTH_URL = 'https://www.tiktok.com/v2/auth/authorize/';
    private const TOKEN_URL = 'https://open.tiktokapis.com/v2/oauth/token/';
    private const USER_INFO_URL = 'https://open.tiktokapis.com/v2/user/info/';

    private const SCOPES = [
        'user.info.basic',
        'video.publish', // Requires approval
        'video.list', // Requires approval
    ];

    public function connect()
    {
        // TikTok uses 'client_key' instead of 'client_id' in some docs, but for v2 auth it's usually client_key. 
        // Laravel services.tiktok.client_id should map to Client Key.
        
        $csrfState = csrf_token();

        $params = [
            'client_key' => config('services.tiktok.client_id'), 
            'response_type' => 'code',
            'scope' => implode(',', self::SCOPES),
            'redirect_uri' => route('channels.tiktok.callback'),
            'state' => $csrfState,
        ];

        return redirect(self::AUTH_URL . '?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::error('TikTok Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'TikTok connection failed.');
            }

            $code = $request->query('code');
            $redirectUri = route('channels.tiktok.callback');

            // Exchange Code
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_key' => config('services.tiktok.client_id'),
                'client_secret' => config('services.tiktok.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('TikTok Token Error', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to exchange TikTok token.');
            }

            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null; // seconds
            $openId = $data['open_id'] ?? null; // user unique ID

            // Fetch User Info
            $userInfoResponse = Http::withToken($accessToken)->get(self::USER_INFO_URL, [
                'fields' => 'open_id,union_id,avatar_url,display_name,username',
            ]);

            $userData = $userInfoResponse->json()['data']['user'] ?? [];
            if (empty($userData)) {
                 // If fields fail, try minimal
                 $userData = ['open_id' => $openId, 'display_name' => 'TikTok User'];
            }

            $uniqueId = $userData['open_id'] ?? $openId;
            $tenant = Auth::user()->tenant;

            $exists = $tenant->channels()
                ->where('type', 'tiktok')
                ->where('config->tiktok_open_id', $uniqueId)
                ->exists();

            if (!$exists) {
                $config = [
                    'tiktok_open_id' => $uniqueId,
                    'union_id' => $userData['union_id'] ?? null,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_in' => $expiresIn,
                    'avatar_url' => $userData['avatar_url'] ?? null,
                    'connected_at' => now()->toDateTimeString(),
                ];

                $displayName = $userData['display_name'] ?? $userData['username'] ?? 'TikTok User';

                $tenant->channels()->create([
                    'name' => $displayName . ' (TikTok)',
                    'type' => 'tiktok',
                    'config' => $config,
                    'is_active' => true,
                ]);

                return redirect()->route('channels.index')->with('success', 'Connected TikTok account.');
            }

            return redirect()->route('channels.index')->with('info', 'TikTok account already connected.');

        } catch (Exception $e) {
            Log::error('TikTok Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('channels.index')->with('error', 'Error connecting TikTok.');
        }
    }
}
