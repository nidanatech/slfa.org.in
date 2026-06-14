<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Channel;
use Exception;

class WhatsAppChannelController extends Controller
{
    private const GRAPH_VERSION = 'v21.0';

    private const SCOPES = [
        'whatsapp_business_management',
        'whatsapp_business_messaging',
        'business_management',
    ];

    private const WEBHOOK_FIELDS = [
        'messages',
        'message_templates',
        'message_echoes', // For reliable sending status
    ];

    /**
     * Connect via OAuth
     */
    public function connect()
    {
        $redirectUri = route('channels.whatsapp_api.callback');
        
        $params = [
            'client_id' => config('services.facebook.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(',', self::SCOPES),
        ];

        $url = 'https://www.facebook.com/' . self::GRAPH_VERSION . '/dialog/oauth?' . http_build_query($params);

        return redirect($url);
    }

    /**
     * Handle Callback
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::error('WhatsApp Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'WhatsApp connection failed: ' . ($request->query('error_reason') ?? 'Unknown error'));
            }

            $code = $request->query('code');
            $redirectUri = route('channels.whatsapp_api.callback');

            // 1. Exchange Code
            $response = Http::asForm()->post('https://graph.facebook.com/' . self::GRAPH_VERSION . '/oauth/access_token', [
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

            $data = $response->json();

            if (!isset($data['access_token'])) {
                return redirect()->route('channels.index')->with('error', 'Token exchange failed.');
            }

            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? null;

            // 2. Fetch WABAs (WhatsApp Business Accounts)
            $wabas = $this->fetchWABAs($accessToken);

            if (empty($wabas)) {
                return redirect()->route('channels.index')->with('error', 'No WhatsApp Business Accounts found.');
            }

            $tenant = Auth::user()->tenant;
            $count = 0;

            // 3. For each WABA, fetch Phone Numbers
            foreach ($wabas as $waba) {
                // Fetch Phone Numbers for this WABA
                $phoneNumbers = $this->fetchPhoneNumbers($waba['id'], $accessToken);

                foreach ($phoneNumbers as $phone) {
                    $phoneId = $phone['id'];
                    $displayName = $phone['display_phone_number'] ?? $phone['verified_name'] ?? 'WhatsApp Number';
                    
                    // Check existence
                    $exists = $tenant->channels()
                        ->where('type', 'whatsapp_api')
                        ->where('config->phone_number_id', $phoneId)
                        ->exists();

                    if (!$exists) {
                        $config = [
                            'waba_id' => $waba['id'],
                            'phone_number_id' => $phoneId,
                            'access_token' => $accessToken, // System User token usually preferred, but using OAuth user token here.
                            'expires_in' => $expiresIn,
                            'connected_at' => now()->toDateTimeString(),
                            'display_number' => $phone['display_phone_number'] ?? null,
                            'verified_name' => $phone['verified_name'] ?? null,
                            'quality_rating' => $phone['quality_rating'] ?? null,
                        ];

                        $tenant->channels()->create([
                            'name' => $displayName . ' (WhatsApp API)',
                            'type' => 'whatsapp_api',
                            'config' => $config,
                            'is_active' => true,
                        ]);

                        // Subscribe WABA to Webhooks
                        $this->subscribe($waba['id'], $accessToken);
                        $count++;
                    }
                }
            }

            if ($count > 0) {
                return redirect()->route('channels.index')->with('success', "Connected {$count} WhatsApp Number(s).");
            }

            return redirect()->route('channels.index')->with('info', 'WhatsApp accounts synced. No new numbers added.');

        } catch (Exception $e) {
            Log::error('WhatsApp Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('channels.index')->with('error', 'Error connecting WhatsApp.');
        }
    }

    private function fetchWABAs($token)
    {
        // Fetch businesses the user has access to, then filter/find client_whatsapp_business_accounts?
        // Or simpler: /me/accounts usually creates confusion with Pages.
        // Best path: /me/businesses -> /<biz_id>/client_whatsapp_business_accounts
        // Or /me?fields=granual_scopes, then find accessible resources?
        
        // Let's try Granular Scopes method or iterate businesses.
        // Simplified: iterate businesses.
        
        $wabas = [];
        $graphUrl = 'https://graph.facebook.com/' . self::GRAPH_VERSION;

        $bizRes = Http::get("$graphUrl/me/businesses", ['access_token' => $token]);
        $businesses = $bizRes->json()['data'] ?? [];

        foreach ($businesses as $biz) {
            $wabaRes = Http::get("$graphUrl/{$biz['id']}/client_whatsapp_business_accounts", [
                'access_token' => $token
            ]);
            $data = $wabaRes->json()['data'] ?? [];
            if ($data) {
                $wabas = array_merge($wabas, $data);
            }
        }
        
        return $wabas;
    }

    private function fetchPhoneNumbers($wabaId, $token)
    {
        $response = Http::get("https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$wabaId}/phone_numbers", [
            'access_token' => $token,
            'fields' => 'id,display_phone_number,verified_name,quality_rating',
        ]);
        
        return $response->json()['data'] ?? [];
    }

    private function subscribe($wabaId, $token)
    {
        // Subscribe the WABA to the App
        $url = "https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$wabaId}/subscribed_apps";
        try {
            Http::post($url, [
                'access_token' => $token,
                // WABA webhooks don't always take 'subscribed_fields' in the POST body the same way Pages do, 
                // but checking v21.0 docs, it is similar.
                'subscribed_fields' => self::WEBHOOK_FIELDS, 
            ]);
        } catch (Exception $e) {
            Log::error("WA Webhook error: {$wabaId}");
        }
    }

    /**
     * Get overview data for the channel
     */
    public function getOverview(\App\Models\Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        
        $service = new \App\Services\WhatsAppOverviewService();
        $data = $service->getOverviewData($channel);

        return response()->json($data);
    }
}
