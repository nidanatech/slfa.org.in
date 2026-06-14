<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HubspotService;
use App\Models\HubspotConnection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HubspotController extends Controller
{
    protected $hubspotService;

    public function __construct(HubspotService $hubspotService)
    {
        $this->hubspotService = $hubspotService;
    }

    /**
     * Redirect to HubSpot OAuth page.
     */
    public function connect()
    {
        return redirect()->away($this->hubspotService->getAuthUrl());
    }

    /**
     * Handle the OAuth callback from HubSpot.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('settings.integrations.hubspot')
                ->with('error', 'HubSpot connection failed: ' . $request->input('error_description'));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('settings.integrations.hubspot')
                ->with('error', 'Missing authorization code.');
        }

        $tokenData = $this->hubspotService->exchangeCode($code);
        if (!$tokenData) {
            return redirect()->route('settings.integrations.hubspot')
                ->with('error', 'Failed to retrieve access token from HubSpot.');
        }

        // Get Hub Info
        $info = $this->hubspotService->getAccessTokenInfo($tokenData['access_token']);
        $hubId = $info['hub_id'] ?? null;

        if (!$hubId) {
            return redirect()->route('settings.integrations.hubspot')
                ->with('error', 'Failed to retrieve Hub ID from HubSpot.');
        }

        // Store or Update Connection
        HubspotConnection::updateOrCreate(
            ['tenant_id' => Auth::user()->tenant_id],
            [
                'hub_id' => $hubId,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($tokenData['expires_in']),
                'settings' => ['user_id' => Auth::id()],
            ]
        );

        return redirect()->route('settings.integrations.hubspot')
            ->with('success', 'HubSpot connected successfully.');
    }
}
