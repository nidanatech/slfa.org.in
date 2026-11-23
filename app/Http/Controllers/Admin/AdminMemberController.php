<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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

    public function downloadIdProof(User $member)
    {
        $path = $member->profile?->id_proof_path;
        abort_unless($path, 404);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path);
        }

        // Files uploaded before private storage was introduced remain readable by admins.
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->download($path);
    }

    public function applicationPhotograph(User $member)
    {
        $path = $member->profile?->photograph_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function approve(User $member)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($member) {
            $member->update(['account_status' => 'active']);
            $member->member?->update(['status' => 'active', 'joined_at' => now()]);
        });
        return back()->with('success', 'Member approved successfully.');
    }

    public function reject(User $member)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($member) {
            $member->update(['account_status' => 'suspended']);
            $member->member?->update(['status' => 'rejected']);
        });
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
                $path = $request->file('id_proof')->store('id_proofs', 'local');
                $profileData['id_proof_path'] = $path;
            }

            $user->profile()->create($profileData);
            Member::create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $profileData['mobile'] ?? null,
                'membership_type' => $request->membership_type,
                'sri_lanka_connection' => implode(', ', $profileData['connection_with_srilanka'] ?? []),
                'occupation' => $profileData['occupation'] ?? null,
                'company_name' => $profileData['business_name'] ?? null,
                'directory_visible' => false,
                'status' => 'active',
                'joined_at' => now(),
            ]);

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
        $directoryMember = $member->member;
        abort_unless($directoryMember, 404);
        $directoryMember->update(['featured' => !$directoryMember->featured]);

        return back()->with('success', 'Directory feature status updated.');
    }

    public function destroy(User $member)
    {
        $member->delete(); 
        return redirect()->route('admin.members.index')->with('success', 'Member deleted.');
    }
}
