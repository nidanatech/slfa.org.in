<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelType;
use App\Models\Tenant;
use App\Models\TenantChannelSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChannelSettingsController extends Controller
{
    public function globalIndex()
    {
        return Inertia::render('Admin/Channels/Index', [
            'channelTypes' => ChannelType::all()
        ]);
    }

    public function updateGlobal(Request $request, ChannelType $channelType)
    {
        $validated = $request->validate([
            'is_global_active' => 'required|boolean'
        ]);

        $channelType->update($validated);

        return back()->with('success', 'Global channel setting updated.');
    }

    public function tenantSettings(Tenant $tenant)
    {
        return Inertia::render('Admin/Tenants/ChannelSettings', [
            'tenant' => $tenant,
            'channelTypes' => ChannelType::all(),
            'tenantSettings' => $tenant->channelSettings()->pluck('is_active', 'channel_type_id')
        ]);
    }

    public function updateTenantSetting(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'channel_type_id' => 'required|string|exists:channel_types,id',
            'is_active' => 'required|boolean'
        ]);

        TenantChannelSetting::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'channel_type_id' => $validated['channel_type_id'],
            ],
            [
                'is_active' => $validated['is_active'],
            ]
        );

        return back()->with('success', 'Tenant channel setting updated.');
    }
}
