<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'membership_type' => 'required|in:individual,business',
            'profile' => 'required|array:address,landline,mobile,whatsapp,personal_email,date_of_birth,gender,occupation,business_name,nature_of_business,date_of_incorporation,connection_with_srilanka',
            'profile.address' => 'required|string|max:2000',
            'profile.mobile' => 'required|string|max:30',
            'profile.landline' => 'nullable|string|max:30',
            'profile.whatsapp' => 'nullable|string|max:30',
            'profile.personal_email' => 'nullable|email|max:255',
            'profile.date_of_birth' => 'nullable|date|before:-18 years',
            'profile.gender' => 'nullable|in:Male,Female,Others',
            'profile.occupation' => 'nullable|string|max:255',
            'profile.business_name' => 'nullable|string|max:255',
            'profile.nature_of_business' => 'nullable|in:Company,LLP,Society,Firm,Others',
            'profile.date_of_incorporation' => 'nullable|date',
            'profile.connection_with_srilanka' => 'required|array|min:1',
            'profile.connection_with_srilanka.*' => 'string|max:100',
            'directory_consent' => 'required|boolean',
            'family_members' => 'nullable|array',
            'family_members.*' => 'array:name,relationship,gender,date_of_birth',
            'family_members.*.name' => 'nullable|string|max:255',
            'family_members.*.relationship' => 'nullable|string|max:255',
            'family_members.*.gender' => 'nullable|in:Male,Female,Others',
            'family_members.*.date_of_birth' => 'nullable|date',
            'business_reps' => 'nullable|array|max:3',
            'business_reps.*' => 'array:name,date_of_birth,gender,designation,email,contact_number',
            'business_reps.*.name' => 'nullable|string|max:255',
            'business_reps.*.date_of_birth' => 'nullable|date',
            'business_reps.*.gender' => 'nullable|in:Male,Female,Others',
            'business_reps.*.designation' => 'nullable|string|max:255',
            'business_reps.*.email' => 'nullable|email|max:255',
            'business_reps.*.contact_number' => 'nullable|string|max:30',
            'id_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('id_proof')->store('id_proofs', 'local');
        abort_unless($path, 500, 'Could not save identity proof.');

        try {
            $user = DB::transaction(function () use ($request, $validated, $path) {
                // 1. Create User
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'member',
                    'account_status' => 'pending',
                ]);

                // 2. Create User Profile
                $profileData = $validated['profile'];
                $profileData['membership_type'] = $request->membership_type;

                $profileData['id_proof_path'] = $path;

                $user->profile()->create($profileData);

                Member::create([
                    'user_id' => $user->id,
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $profileData['mobile'],
                    'membership_type' => $request->membership_type,
                    'sri_lanka_connection' => implode(', ', $profileData['connection_with_srilanka']),
                    'occupation' => $profileData['occupation'] ?? null,
                    'company_name' => $profileData['business_name'] ?? null,
                    'directory_visible' => $request->boolean('directory_consent'),
                    'directory_consent_at' => $request->boolean('directory_consent') ? now() : null,
                    'status' => 'pending',
                ]);

                // 3. Create Family Members or Business Reps
                if ($request->membership_type === 'individual' && !empty($validated['family_members'])) {
                    foreach ($validated['family_members'] as $member) {
                        // Only save if name is provided
                        if (!empty($member['name'])) {
                            $user->familyMembers()->create($member);
                        }
                    }
                } elseif ($request->membership_type === 'business' && !empty($validated['business_reps'])) {
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
            throw $exception;
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Your application was submitted and is awaiting review.');
    }
}
