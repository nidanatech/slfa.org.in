<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardAssistantService;

class TenantSwitcherController extends Controller
{
    public function switch(Request $request, Tenant $tenant)
    {
        $user = Auth::user();

        // Security: Check if user actually belongs to this tenant
        if (!$user->tenants->contains($tenant->id)) {
            return redirect()->back()->with('error', 'You do not have access to this business.');
        }

        // Update active tenant
        $user->update(['tenant_id' => $tenant->id]);

        return \Inertia\Inertia::location(route('dashboard'));
    }

    public function create(Request $request, \App\Services\DashboardAssistantService $service)
    {
        $user = Auth::user();

        // 1. Create a new "pending" tenant
        $tenant = Tenant::create([
            'name' => 'New Business',
        ]);

        // 2. Associate user with this new tenant
        $user->tenants()->attach($tenant);
        
        // 3. Set as active tenant
        $user->update(['tenant_id' => $tenant->id]);

        // 4. Initialize Dashboard Assistant (Best effort)
        try {
            $service->ensureAssistant($tenant);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Could not initialize dashboard assistant for new tenant {$tenant->id}: " . $e->getMessage());
        }

        // 5. Redirect to onboarding form
        return redirect()->route('onboarding.index')
            ->with('info', 'Lets set up your new business.');
    }
}
