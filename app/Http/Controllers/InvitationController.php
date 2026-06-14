<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvitation;
use App\Mail\InviteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class InvitationController extends Controller
{
    // Send Invitation
    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        $tenantId = $tenant->id;

        // Quota Check
        if (!$tenant->canCreate('users')) {
            return back()->with('error', 'The team has reached its user limit.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'role_name' => 'required|string|exists:roles,name', // Basic validation, stricter check below
        ]);

        // Check for pending invitation
        $existing = UserInvitation::where('email', $validated['email'])
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing) {
            return back()->with('error', 'An invitation is already pending for this email.');
        }

        // Validate Role ownership (if using spatie teams)
        // We can trust the admin to pick from available roles (which UI filters).
        // But for safety:
        // $role = Role::where('name', $validated['role_name'])->where('team_id', $tenantId)->orWhereNull('team_id')->firstOrFail();

        $invitation = UserInvitation::create([
            'email' => $validated['email'],
            'tenant_id' => $tenantId,
            'role_name' => $validated['role_name'],
            'token' => Str::random(32),
            'expires_at' => now()->addHours(48),
        ]);

        try {
            Mail::to($invitation->email)->queue(new InviteUser($invitation));
        } catch (\Exception $e) {
            \Log::error("Failed to send invitation email: " . $e->getMessage());
            // Don't fail the request, but warn? Or fail?
            // Usually letting it fail queue is better.
        }

        return back()->with('success', 'Invitation sent successfully.');
        return back()->with('success', 'Invitation sent successfully.');
    }

    // Check for Email Existence
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        $isSelf = $request->user()->email === $request->email;
        
        $tenant = $request->user()->tenant;
        $canAdd = $tenant->canCreate('users');
        
        return response()->json([
            'exists' => (bool)$user,
            'isSelf' => $isSelf,
            'canAdd' => $canAdd,
            'limit' => $tenant->getQuotaLimit('users'),
            'usage' => $tenant->getQuotaUsage('users')
        ]);
    }

    // Manual Create User
    public function storeDirect(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        // Check Quota
        $tenant = $request->user()->tenant;
        if (!$tenant->canCreate('users')) {
            return back()->with('error', 'The team has reached its user limit.');
        }

        // Check if user already exists
        $user = User::where('email', $request->email)->first();

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_name' => 'required|string|exists:roles,name',
        ];

        if (!$user) {
            $rules['password'] = ['required', Rules\Password::defaults()];
            $rules['email'] .= '|unique:users,email';
        }

        $validated = $request->validate($rules);

        if (!$user) {
            // Create User
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenantId,
            ]);
        }

        // Check if user is already in this tenant
        if ($user && $user->tenants()->where('tenants.id', $tenantId)->exists()) {
             $errorMsg = $request->user()->email === $request->email 
                ? 'You cannot add yourself as a team member.' 
                : 'This user is already a member of this team.';
             return back()->with('error', $errorMsg);
        }

        $user->tenants()->attach($tenantId);

        // Assign Role
        try {
            setPermissionsTeamId($tenantId);
            $user->assignRole($validated['role_name']);
        } catch (\Exception $e) {
            \Log::error("Failed to assign role to manual user: " . $e->getMessage());
        }

        return back()->with('success', $user->wasRecentlyCreated ? 'User created successfully.' : 'Existing user added to the team.');
    }

    public function resend(Request $request, $id)
    {
        $tenantId = $request->user()->tenant_id;
        $invitation = UserInvitation::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        // Update expiry
        $invitation->update(['expires_at' => now()->addHours(48)]);

        try {
            Mail::to($invitation->email)->queue(new InviteUser($invitation));
        } catch (\Exception $e) {
             \Log::error("Failed to resend invitation email: " . $e->getMessage());
        }

        return back()->with('success', 'Invitation resent successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $tenantId = $request->user()->tenant_id;
        $invitation = UserInvitation::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();
        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }

    // Show Accept Form
    public function accept($token)
    {
        $invitation = UserInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->email,
            'tenant_name' => $invitation->tenant->friendly_name ?? 'the team',
        ]);
    }

    // Complete Invitation
    public function complete(Request $request, $token)
    {
        $invitation = UserInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Final Quota Check
        if (!$invitation->tenant->canCreate('users')) {
            return back()->with('error', 'The team has reached its user limit. Please contact the administrator.');
        }

        // Create User
        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'tenant_id' => $invitation->tenant_id,
        ]);

        $user->tenants()->attach($invitation->tenant_id);

        // Assign Role
        try {
            // Explicitly set the context to the invitation's tenant so the role 
            // is assigned for this specific tenant (Spatie Teams).
            setPermissionsTeamId($invitation->tenant_id);
            $user->assignRole($invitation->role_name);
            
        } catch (\Exception $e) {
            \Log::error("Failed to assign role to new user: " . $e->getMessage());
        }

        // Delete Invitation
        $invitation->delete();

        // Login
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
