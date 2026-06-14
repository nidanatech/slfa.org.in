<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function index()
    {
        return Inertia::render('Onboarding/Index');
    }

    public function store(Request $request)
    {
        // 1. Validate Input First
        $validated = $request->validate([
            'friendly_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            // Step 2
            'address_street' => 'required|string|max:255',
            'address_city' => 'required|string|max:100',
            'address_state' => 'required|string|max:100',
            'address_zip' => 'required|string|max:20',
            'address_country' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string',
            // Step 3
            'auth_rep_first_name' => 'required|string|max:100',
            'auth_rep_last_name' => 'required|string|max:100',
            'auth_rep_email' => 'required|email|max:100',
            'auth_rep_phone' => 'required|string|max:20',
            'auth_rep_job_position' => 'required|string|max:100',
        ]);

        $tenant = Auth::user()->tenant;

        // 2. Create or Update Tenant
        if (!$tenant) {
             $tenant = \App\Models\Tenant::create([
                 'name' => $validated['legal_name'],
             ]);
             
             $user = Auth::user();
             $user->tenant_id = $tenant->id;
             $user->save();
             $user->tenants()->syncWithoutDetaching([$tenant->id]);
        } else {
            $tenant->update(['name' => $validated['legal_name']]);
        }

        // 3. Create Business Profile
        $createData = [
            'tenant_id' => $tenant->id,
            'friendly_name' => $validated['friendly_name'],
            'legal_name' => $validated['legal_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'currency' => $validated['currency'],
            'timezone' => $validated['timezone'],
            'address_street' => $validated['address_street'],
            'address_city' => $validated['address_city'],
            'address_state' => $validated['address_state'],
            'address_zip' => $validated['address_zip'],
            'address_country' => $validated['address_country'],
            'authorized_representative' => [
                'first_name' => $validated['auth_rep_first_name'],
                'last_name' => $validated['auth_rep_last_name'],
                'email' => $validated['auth_rep_email'],
                'phone' => $validated['auth_rep_phone'],
                'job_position' => $validated['auth_rep_job_position'],
            ],
        ];

        BusinessProfile::create($createData);

        return redirect()->route('dashboard')->with('success', 'Onboarding Completed!');
    }
}
