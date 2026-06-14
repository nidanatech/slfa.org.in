<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorRatingCriteria;
use App\Services\SocialMediaScannerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VendorController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = Vendor::where('tenant_id', auth()->user()->tenant_id);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->risk_level && $request->risk_level !== 'all') {
            $query->where('risk_level', $request->risk_level);
        }

        $vendors = $query->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'status', 'risk_level']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,blacklisted,pending',
            'business_profiles' => 'nullable|array',
            'tax_id' => 'nullable|string|max:50',
            'bank_details' => 'nullable|array',
            'bank_details.account_number' => 'nullable|string',
            'bank_details.ifsc' => 'nullable|string',
            'bank_details.bank_name' => 'nullable|string',
            'region' => 'nullable|string|max:100',
        ]);

        $vendor = Vendor::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
            'overall_score' => 0,
            'risk_level' => 'low',
            'onboarding_status' => 'draft',
        ]);

        return redirect()->route('vendors.show', $vendor->id)
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        $vendor->load(['reviews.user', 'reviews.scores.criteria', 'socialPosts', 'documents']);
        
        // Calculate detailed stats if needed, or pass reviews for frontend calc
        
        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
            'criteria' => VendorRatingCriteria::where('tenant_id', auth()->user()->tenant_id)->get(),
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,blacklisted,pending',
        ]);

        $vendor->update($validated);

        return back()->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $this->authorize('delete', $vendor);
        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor deleted successfully.');
    }

    public function createPortalUser(Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        if (!$vendor->email) {
            return back()->with('error', 'Vendor must have an email address to create a user.');
        }

        // Check if user exists
        $existingUser = \App\Models\User::where('email', $vendor->email)->first();
        if ($existingUser) {
            if ($existingUser->vendor_id) {
                return back()->with('error', 'User with this email is already linked to a vendor.');
            }
            $existingUser->update(['vendor_id' => $vendor->id]);
            return back()->with('success', 'Existing user linked to this vendor.');
        }

        // Create new user
        $password = \Illuminate\Support\Str::random(10);
        $user = \App\Models\User::create([
            'name' => $vendor->name,
            'email' => $vendor->email,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'email_verified_at' => now(),
            'role' => 'vendor', // Assuming role column exists or handled by logic
            'tenant_id' => $vendor->tenant_id,
            'vendor_id' => $vendor->id,
        ]);

        // In real app, send email with password. Here we flash it.
        return back()->with('success', "Portal user created. Password: $password");
    }

    public function updateStatus(Vendor $vendor, Request $request)
    {
        $this->authorize('update', $vendor);
        
        $vendor->update(['onboarding_status' => $request->status]);

        return back()->with('success', "Vendor status updated to {$request->status}.");
    }

    public function scanSocial(Vendor $vendor, SocialMediaScannerService $scanner)
    {
        $this->authorize('update', $vendor);

        $posts = $scanner->scan($vendor);

        return back()->with('success', 'Social media scan completed. ' . count($posts) . ' posts analyzed.');
    }
}
