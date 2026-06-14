<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class LinkedinChannelController extends Controller
{
    private const AUTH_URL = 'https://www.linkedin.com/oauth/v2/authorization';
    private const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
    private const API_URL = 'https://api.linkedin.com/v2';

    private const SCOPES = [
        'openid',
        'profile',
        'w_member_social',
        'email',
    ];

    public function connect()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.linkedin.client_id'),
            'redirect_uri' => route('channels.linkedin.callback'),
            'scope' => implode(' ', self::SCOPES), // LinkedIn uses space separator
        ];

        return redirect(self::AUTH_URL . '?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::error('LinkedIn Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'LinkedIn connection failed.');
            }

            $code = $request->query('code');
            $redirectUri = route('channels.linkedin.callback');

            // Exchange Code
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('LinkedIn Token Error', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to exchange LinkedIn token.');
            }

            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? null;

            // Fetch Profile (using OpenID /userinfo or v2/me)
            // v2/me is standard for ID, but /v2/userinfo (OIDC) gives simpler name/picture structure sometimes.
            // Let's use v2/userinfo if using 'openid' scope.
            
            $profileResponse = Http::withToken($accessToken)->get('https://api.linkedin.com/v2/userinfo');
            
            if ($profileResponse->failed()) {
                 // Fallback to v2/me if openid fails?
                 $profileResponse = Http::withToken($accessToken)->get(self::API_URL . '/me');
            }
            
            $profile = $profileResponse->json();
            $linkedinId = $profile['sub'] ?? $profile['id'] ?? null; // 'sub' is standard OIDC subject

            if (!$linkedinId) {
                return redirect()->route('channels.index')->with('error', 'Failed to fetch LinkedIn Profile.');
            }

            $tenant = Auth::user()->tenant;
            
            // Check existence
            $exists = $tenant->channels()
                ->where('type', 'linkedin')
                ->where('config->linkedin_id', $linkedinId)
                ->exists();

            if (!$exists) {
                $config = [
                    'linkedin_id' => $linkedinId,
                    'access_token' => $accessToken,
                    'expires_in' => $expiresIn,
                    'profile_picture_url' => $profile['picture'] ?? null,
                    'email' => $profile['email'] ?? null,
                    'connected_at' => now()->toDateTimeString(),
                ];

                $updatedName = $profile['name'] ?? ($profile['given_name'] . ' ' . $profile['family_name']) ?? 'LinkedIn Member';

                $tenant->channels()->create([
                    'name' => $updatedName . ' (LinkedIn)',
                    'type' => 'linkedin',
                    'config' => $config,
                    'is_active' => true,
                ]);

                return redirect()->route('channels.index')->with('success', 'Connected LinkedIn profile.');
            }

            return redirect()->route('channels.index')->with('info', 'LinkedIn profile already connected.');

        } catch (Exception $e) {
            Log::error('LinkedIn Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('channels.index')->with('error', 'Error connecting LinkedIn.');
        }
    }
}
