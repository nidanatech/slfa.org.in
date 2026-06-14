<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = \Illuminate\Support\Facades\Auth::user()->tenant;
        $businessProfile = $tenant ? \App\Models\BusinessProfile::where('tenant_id', $tenant->id)->first() : null;
        
        // Get social planner settings
        $socialPlannerSettings = $tenant ? \App\Models\SocialPlannerSettings::getOrCreateForTenant($tenant->id) : null;
        
        // Get users for approver selection
        $users = $tenant ? $tenant->users()
            ->select('users.id', 'first_name', 'last_name', 'email')
            ->with('roles')
            ->get() : [];

        // Get Pending Invitations
        $invitations = $tenant ? \App\Models\UserInvitation::where('tenant_id', $tenant->id)
            ->where('expires_at', '>', now())
            ->get() : [];

        // RBAC Data
        $roles = [];
        $permissions = [];
        try {
            if (class_exists(\Spatie\Permission\Models\Permission::class)) {
                $permissions = \Spatie\Permission\Models\Permission::all()->map(function ($perm) {
                    $parts = explode('.', $perm->name);
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                        'category' => count($parts) > 1 ? ucfirst($parts[0]) : 'General',
                        'label' => count($parts) > 1 ? ucfirst(str_replace('_', ' ', $parts[1])) : $perm->name,
                    ];
                })->groupBy('category');

                if ($tenant) {
                     $roles = \Spatie\Permission\Models\Role::where('team_id', $tenant->id)
                        ->orWhereNull('team_id')
                        ->with('permissions')
                        ->get()
                        ->unique('name')
                        ->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'is_system' => is_null($role->team_id),
                                'permissions' => $role->permissions->pluck('name'),
                                'users_count' => $role->users()->count(),
                            ];
                        })
                        ->values(); // Reset keys after unique
                }
            }
        } catch (\Throwable $e) {
            \Log::error('RBAC Loading Error: ' . $e->getMessage());
        }

        return Inertia::render('Settings/Index', [
            'businessProfile' => $businessProfile,
            'socialPlannerSettings' => $socialPlannerSettings,
            'socialPlannerSettings' => $socialPlannerSettings,
            'users' => $users,
            'invitations' => $invitations,
            'roles' => $roles,
            'permissions' => $permissions,
            'paymentModes' => $tenant ? \App\Models\PaymentMode::where('tenant_id', $tenant->id)->get() : [],
            'subscription' => $tenant ? $tenant->subscriptions()->orderBy('created_at', 'desc')->with('plan')->first() : null,
            'availablePlans' => \App\Models\Plan::where('is_active', true)->get(),
        ]);
    }


    public function updateBusinessProfile(Request $request)
    {
        $tenant = \Illuminate\Support\Facades\Auth::user()->tenant;
        
        $validated = $request->validate([
            'friendly_name' => 'nullable|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string',
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_zip' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2500', // 2.5MB max
            
            // Auth Rep validation
            'auth_rep_first_name' => 'nullable|string|max:100',
            'auth_rep_last_name' => 'nullable|string|max:100',
            'auth_rep_email' => 'nullable|email|max:100',
            'auth_rep_job_position' => 'nullable|string|max:100',
            'auth_rep_phone' => 'nullable|string|max:20',
        ]);

        if (!$tenant) {
            return redirect()->back()->with('error', 'No tenant associated with this user.');
        }

        $createData = [
            'tenant_id' => $tenant->id,
            'friendly_name' => $validated['friendly_name'] ?? null,
            'legal_name' => $validated['legal_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'timezone' => $validated['timezone'] ?? 'UTC',
            'address_street' => $validated['address_street'] ?? null,
            'address_city' => $validated['address_city'] ?? null,
            'address_state' => $validated['address_state'] ?? null,
            'address_zip' => $validated['address_zip'] ?? null,
            'address_country' => $validated['address_country'] ?? null,
            'authorized_representative' => [
                'first_name' => $validated['auth_rep_first_name'] ?? null,
                'last_name' => $validated['auth_rep_last_name'] ?? null,
                'email' => $validated['auth_rep_email'] ?? null,
                'job_position' => $validated['auth_rep_job_position'] ?? null,
                'phone' => $validated['auth_rep_phone'] ?? null,
            ],
        ];

        // Handle File Upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $createData['logo_url'] = '/storage/' . $path;
        }

        try {
            // Update or Create
            $profile = \App\Models\BusinessProfile::updateOrCreate(
                ['tenant_id' => $tenant->id],
                $createData
            );
        } catch (\Exception $e) {
             \Log::error('Business Profile Update Error: ' . $e->getMessage());
             return redirect()->back()->with('error', 'Failed to save profile: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Business profile updated successfully.');
    }
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'extension' => 'nullable|string|max:10',
            'online_status' => 'nullable|in:active,inactive,idle',
            'profile_image' => 'nullable|image|max:2500', // 2.5MB
        ]);

        $updateData = [
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'last_name' => $validated['last_name'] ?? $user->last_name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'extension' => $validated['extension'] ?? $user->extension,
            'online_status' => $validated['online_status'] ?? $user->online_status,
        ];

        // Combine First and Last Name for the main name field if needed, or keep separate. 
        // Laravel default User uses 'name'. Let's sync it.
        $fullName = trim(($validated['first_name'] ?? $user->first_name) . ' ' . ($validated['last_name'] ?? $user->last_name));
        if (!empty($fullName)) {
            $updateData['name'] = $fullName;
        }

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('avatars', 'public');
            $updateData['profile_image'] = '/storage/' . $path;
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }
}
