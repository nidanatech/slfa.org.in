<?php

namespace App\Http\Controllers;

use App\Models\GhlConnection;
use App\Services\GhlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GhlController extends Controller
{
    protected $ghlService;

    public function __construct(GhlService $ghlService)
    {
        $this->ghlService = $ghlService;
    }

    public function connect()
    {
        return redirect($this->ghlService->getAuthUrl());
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect()->route('settings.integrations.ghl')->with('error', 'No code provided from GHL.');
        }

        $data = $this->ghlService->exchangeCode($code);
        if (!$data) {
            return redirect()->route('settings.integrations.ghl')->with('error', 'Failed to exchange code for tokens.');
        }

        GhlConnection::updateOrCreate(
            ['tenant_id' => Auth::user()->tenant_id],
            [
                'location_id' => $data['locationId'] ?? null,
                'company_id' => $data['companyId'] ?? null,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]
        );

        return redirect()->route('settings.integrations.ghl')->with('success', 'GoHighLevel connected successfully.');
    }

    public function syncNow()
    {
        $connection = GhlConnection::where('tenant_id', Auth::user()->tenant_id)->first();
        if (!$connection) {
            return back()->with('error', 'No GHL connection found.');
        }

        $pipelinesSynced = $this->ghlService->syncPipelines($connection);
        $contactsSynced = $this->ghlService->syncContacts($connection);
        $opportunitiesSynced = $this->ghlService->syncOpportunities($connection);

        if ($pipelinesSynced && $contactsSynced && $opportunitiesSynced) {
            return back()->with('success', 'Synchronization completed successfully.');
        }

        return back()->with('error', 'Synchronization failed partially.');
    }
}
