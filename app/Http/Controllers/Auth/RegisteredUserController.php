<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\FamilyMember;
use App\Models\BusinessRepresentative;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'membership_type' => 'required|in:individual,business',
            'profile' => 'required|array',
            'family_members' => 'nullable|array',
            'business_reps' => 'nullable|array',
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = DB::transaction(function () use ($request) {
            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'member',
                'account_status' => 'pending',
            ]);

            // 2. Create User Profile
            $profileData = $request->profile;
            $profileData['membership_type'] = $request->membership_type;
            
            if ($request->hasFile('id_proof')) {
                $path = $request->file('id_proof')->store('id_proofs', 'public');
                $profileData['id_proof_path'] = $path;
            }

            $user->profile()->create($profileData);

            // 3. Create Family Members or Business Reps
            if ($request->membership_type === 'individual' && !empty($request->family_members)) {
                foreach ($request->family_members as $member) {
                    // Only save if name is provided
                    if (!empty($member['name'])) {
                        $user->familyMembers()->create($member);
                    }
                }
            } elseif ($request->membership_type === 'business' && !empty($request->business_reps)) {
                foreach ($request->business_reps as $rep) {
                    if (!empty($rep['name'])) {
                        $user->businessRepresentatives()->create($rep);
                    }
                }
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('payment.gateway', absolute: false));
    }
}
