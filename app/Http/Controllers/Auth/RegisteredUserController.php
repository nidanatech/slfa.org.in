<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use App\Http\Requests\Auth\StoreMembershipApplicationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreMembershipApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $path = $request->file('id_proof')->store('id_proofs', 'local');
        abort_unless($path, 500, 'Could not save identity proof.');
        $photographPath = $request->file('photograph')?->store('application_photographs', 'local');
        if ($request->hasFile('photograph') && ! $photographPath) {
            Storage::disk('local')->delete($path);
            abort(500, 'Could not save applicant photograph.');
        }

        try {
            $user = DB::transaction(function () use ($validated, $path, $photographPath) {
                $accountName = $validated['membership_type'] === 'business'
                    ? $validated['profile']['business_name']
                    : $validated['name'];
                // 1. Create User
                $user = User::create([
                    'name' => $accountName,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'member',
                    'account_status' => 'pending',
                ]);

                // 2. Create User Profile
                $profileData = $validated['profile'];
                $profileData['membership_type'] = $validated['membership_type'];
                $profileData['application_declaration_at'] = now();

                $profileData['id_proof_path'] = $path;
                $profileData['photograph_path'] = $photographPath;

                $user->profile()->create($profileData);

                Member::create([
                    'user_id' => $user->id,
                    'full_name' => $validated['membership_type'] === 'business' ? $profileData['business_name'] : $user->name,
                    'email' => $user->email,
                    'phone' => $profileData['mobile'],
                    'membership_type' => $validated['membership_type'],
                    'sri_lanka_connection' => implode(', ', $profileData['connection_with_srilanka'] ?? []),
                    'occupation' => $profileData['occupation'] ?? null,
                    'company_name' => $profileData['business_name'] ?? null,
                    'directory_visible' => (bool) $validated['directory_consent'],
                    'directory_consent_at' => $validated['directory_consent'] ? now() : null,
                    'status' => 'pending',
                ]);

                // 3. Create the two business representatives required by the source form.
                if ($validated['membership_type'] === 'business' && !empty($validated['business_reps'])) {
                    foreach ($validated['business_reps'] as $rep) {
                        if (!empty($rep['name'])) {
                            $user->businessRepresentatives()->create($rep);
                        }
                    }
                }

                return $user;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            if ($photographPath) {
                Storage::disk('local')->delete($photographPath);
            }
            throw $exception;
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Your application was submitted and is awaiting review.');
    }
}
