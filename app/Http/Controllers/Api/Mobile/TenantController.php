<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenants = $user->tenants()
            ->with('businessProfile')
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo' => $tenant->businessProfile?->logo_url,
                ];
            });

        return response()->json([
            'tenants' => $tenants,
            'active_tenant_id' => $user->tenant_id,
        ]);
    }

    public function select(Request $request, Tenant $tenant)
    {
        $user = $request->user();

        // Security check
        if (!$user->tenants->contains($tenant->id)) {
            return response()->json(['error' => 'Unauthorized access to this business.'], 403);
        }

        // Update active tenant
        $user->update(['tenant_id' => $tenant->id]);

        return response()->json([
            'message' => 'Business switched successfully',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ]
        ]);
    }
}
