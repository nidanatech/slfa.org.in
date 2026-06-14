<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class YoutubeChannelController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_URL = 'https://www.googleapis.com/youtube/v3';

    private const SCOPES = [
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.force-ssl',
    ];

    public function connect()
    {
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('channels.youtube.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline', // Critical for getting refresh_token
            'prompt' => 'consent', // Forces refresh_token logic
        ];

        return redirect(self::AUTH_URL . '?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::error('YouTube Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'YouTube connection failed.');
            }

            $code = $request->query('code');
            $redirectUri = route('channels.youtube.callback');

            // Exchange Code
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('YouTube Token Error', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to exchange YouTube token.');
            }

            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null;

            // Fetch Channel Info
            // channels?part=snippet&mine=true
            $channelRes = Http::withToken($accessToken)->get(self::API_URL . '/channels', [
                'part' => 'snippet',
                'mine' => 'true',
            ]);

            $channelData = $channelRes->json()['items'][0] ?? null;

            if (!$channelData) {
                return redirect()->route('channels.index')->with('error', 'No YouTube Channel found for this account.');
            }

            $youtubeId = $channelData['id'];
            $snippet = $channelData['snippet'];
            $title = $snippet['title'];
            $thumbnail = $snippet['thumbnails']['default']['url'] ?? null;

            $tenant = Auth::user()->tenant;

            $exists = $tenant->channels()
                ->where('type', 'youtube')
                ->where('config->youtube_id', $youtubeId)
                ->exists();

            if (!$exists) {
                $config = [
                    'youtube_id' => $youtubeId,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken, 
                    'expires_in' => $expiresIn,
                    'connected_at' => now()->toDateTimeString(),
                    'thumbnail_url' => $thumbnail,
                ];

                $tenant->channels()->create([
                    'name' => $title . ' (YouTube)',
                    'type' => 'youtube',
                    'config' => $config,
                    'is_active' => true,
                ]);

                return redirect()->route('channels.index')->with('success', 'Connected YouTube Channel.');
            }
            
            // If exists, strictly we might want to update the refresh token if provided
            if ($refreshToken) {
                $channel = $tenant->channels()
                    ->where('type', 'youtube')
                    ->where('config->youtube_id', $youtubeId)
                    ->first();
                    
                $config = $channel->config;
                $config['refresh_token'] = $refreshToken; // Update if new one provided
                $config['access_token'] = $accessToken;
                $channel->config = $config;
                $channel->save();
            }

            return redirect()->route('channels.index')->with('info', 'YouTube Channel already connected.');

        } catch (Exception $e) {
            Log::error('YouTube Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('channels.index')->with('error', 'Error connecting YouTube.');
        }
    }
}
