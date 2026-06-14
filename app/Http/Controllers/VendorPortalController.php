<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class VendorPortalController extends Controller
{
    public function dashboard()
    {
        $vendor = Auth::user()->vendor;
        
        return Inertia::render('Portal/Dashboard', [
            'vendor' => $vendor->load('documents'),
            'stats' => [
                'profile_completion' => $this->calculateCompletion($vendor),
                'risk_score' => $vendor->social_risk_score ?? 0,
            ]
        ]);
    }

    public function profile()
    {
        return Inertia::render('Portal/Profile', [
            'vendor' => Auth::user()->vendor,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'bank_details' => 'nullable|array',
            'bank_details.account_number' => 'nullable|string',
            'bank_details.ifsc' => 'nullable|string',
            'bank_details.bank_name' => 'nullable|string',
            'social_handles' => 'nullable|array',
        ]);

        $vendor->update($validated);

        if ($vendor->onboarding_status === 'draft') {
            $vendor->update(['onboarding_status' => 'submitted']);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function documents()
    {
        return Inertia::render('Portal/Documents', [
            'vendor' => Auth::user()->vendor->load('documents'),
        ]);
    }

    private function calculateCompletion($vendor)
    {
        $fields = ['name', 'email', 'phone', 'tax_id', 'bank_details', 'description'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($vendor->$field)) {
                $filled++;
            }
        }
        return round(($filled / count($fields)) * 100);
    }
}
