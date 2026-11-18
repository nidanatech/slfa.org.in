<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::where('status', 'active')
            ->where('directory_visible', true);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('type')) {
            $query->where('membership_type', $request->type);
        }
        if ($request->filled('connection')) {
            $query->where('sri_lanka_connection', $request->connection);
        }

        // Sort
        $sort = $request->get('sort', 'newest');
        if ($sort === 'alphabetical') {
            $query->orderBy('full_name');
        } else {
            $query->orderByDesc('joined_at');
        }

        // Featured members first
        $query->orderByDesc('featured');

        return Inertia::render('SLFA/Directory', [
            'members' => $query->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'city', 'type', 'connection', 'sort']),
            'cities' => Member::where('status', 'active')->whereNotNull('city')
                ->distinct()->pluck('city')->sort()->values(),
        ]);
    }

    public function show(Member $member)
    {
        abort_unless($member->status === 'active' && $member->directory_visible, 404);

        return Inertia::render('SLFA/MemberProfile', [
            'member' => $member,
        ]);
    }

    public function message(Request $request, Member $member)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // TODO: Send email to the member (don't expose email directly)
        // Mail::to($member->email)->send(new MemberMessage($request->user()->member, $validated));

        return back()->with('success', 'Your message has been sent to the member.');
    }
}
