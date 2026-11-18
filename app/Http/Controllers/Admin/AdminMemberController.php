<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminMemberController extends Controller
{
    public function index(Request $request)
    {
        // Don't show admin accounts in the member list
        $query = User::with('profile')->where('role', '!=', 'admin')->latest();
        
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        return Inertia::render('Admin/Members/Index', [
            'members' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(User $member)
    {
        $member->load('profile', 'familyMembers', 'businessRepresentatives');
        return Inertia::render('Admin/Members/Show', [
            'member' => $member,
        ]);
    }

    public function approve(User $member)
    {
        $member->update(['account_status' => 'active']);
        return back()->with('success', 'Member approved successfully.');
    }

    public function reject(User $member)
    {
        $member->update(['account_status' => 'suspended']);
        return back()->with('success', 'Member application rejected.');
    }

    public function create()
    {
        return Inertia::render('Admin/Members/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'membership_type' => 'required|in:individual,business',
            'profile' => 'required|array',
            'family_members' => 'nullable|array',
            'business_reps' => 'nullable|array',
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'member',
                'account_status' => 'active', // Since it's admin created
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

        return redirect()->route('admin.members.show', $user)->with('success', 'Member created manually. You can now edit their full profile details.');
    }

    public function update(Request $request, User $member)
    {
        $validated = $request->validate([
            'date_of_application' => 'nullable|date',
            'entrance_fee_received' => 'nullable|date',
            'membership_fee_received' => 'nullable|date',
            'mode_of_payment' => 'nullable|string',
            'payment_reference_number' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'proposed_by' => 'nullable|string',
            'seconded_by' => 'nullable|string',
            'approved_by' => 'nullable|string',
            'date_of_enrollment' => 'nullable|date',
            'membership_number' => 'nullable|string',
            'admin_remarks' => 'nullable|string',
        ]);

        $member->profile()->update($validated);

        return back()->with('success', 'Member admin data updated successfully.');
    }

    public function toggleFeatured(User $member)
    {
        // Currently there's no featured column on User or UserProfile. 
        // We'll just ignore or implement later.
        return back();
    }

    public function destroy(User $member)
    {
        $member->delete(); 
        return redirect()->route('admin.members.index')->with('success', 'Member deleted.');
    }
}
