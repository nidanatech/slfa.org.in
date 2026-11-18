<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class MembershipController extends Controller
{
    public function create()
    {
        return Inertia::render('SLFA/Join');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'full_name' => 'required|string|max:255',
            'membership_type' => 'required|in:individual,business',
            'sri_lanka_connection' => 'nullable|string',
            'occupation' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:-18 years',
            'directory_consent' => 'nullable|boolean',
        ]);

        // Create user account
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
        ]);

        // Create member profile
        $member = Member::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'membership_type' => $validated['membership_type'],
            'sri_lanka_connection' => $validated['sri_lanka_connection'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'city' => $validated['city'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'status' => 'pending',
            'directory_visible' => $validated['directory_consent'] ?? true,
            'directory_consent_at' => ($validated['directory_consent'] ?? true) ? now() : null,
        ]);

        // TODO: Fire event to notify committee
        // event(new MemberApplicationSubmitted($member));

        return redirect()->route('home')->with('success',
            'Your membership application has been submitted! You will receive an email once it is reviewed by the committee.'
        );
    }
}
