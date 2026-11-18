<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'membership' => $request->user()->load('profile', 'member')->profile?->only([
                'address', 'mobile', 'whatsapp', 'personal_email', 'occupation', 'business_name',
            ]),
            'directoryVisible' => $request->user()->member?->directory_visible ?? false,
            'shareContactInfo' => $request->user()->member?->show_contact_info ?? false,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        DB::transaction(function () use ($request) {
            $request->user()->save();
            $request->user()->member?->update([
                'full_name' => $request->user()->name,
                'email' => $request->user()->email,
            ]);
        });

        return Redirect::route('profile.edit');
    }

    public function updateMembership(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:2000'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'directory_visible' => ['required', 'boolean'],
            'show_contact_info' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $visible = $data['directory_visible'];
            $shareContact = $data['show_contact_info'];
            unset($data['directory_visible'], $data['show_contact_info']);
            $request->user()->profile()->update($data);
            $member = $request->user()->member;
            if ($member) {
                $member->update([
                    'phone' => $data['mobile'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'company_name' => $data['business_name'] ?? null,
                    'directory_visible' => $visible,
                    'show_contact_info' => $shareContact,
                    'directory_consent_at' => $visible ? ($member->directory_consent_at ?? now()) : null,
                ]);
            }
        });

        return Redirect::route('profile.edit')->with('success', 'Membership profile saved.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
