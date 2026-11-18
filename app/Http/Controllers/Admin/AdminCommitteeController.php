<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminCommitteeController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Committee/Index', [
            'leadershipSetup' => CommitteeMember::where('type', 'Leadership')->orderBy('display_order')->get(),
            'executiveCommittee' => CommitteeMember::where('type', 'Executive')->orderBy('display_order')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Committee/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type' => 'required|string',
            'show_in_website' => 'boolean',
            'photo' => 'nullable|image|max:2048',
            'display_order' => 'integer',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('committee', 'public');
        }

        CommitteeMember::create($validated);

        return redirect()->route('admin.committee.index')->with('success', 'Committee member added.');
    }

    public function edit(CommitteeMember $committee)
    {
        return Inertia::render('Admin/Committee/Edit', ['member' => $committee]);
    }

    public function update(Request $request, CommitteeMember $committee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type' => 'required|string',
            'show_in_website' => 'boolean',
            'photo' => 'nullable|image|max:2048',
            'display_order' => 'integer',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('committee', 'public');
        }

        $committee->update($validated);

        return redirect()->route('admin.committee.index')->with('success', 'Committee member updated.');
    }

    public function destroy(CommitteeMember $committee)
    {
        $committee->delete();
        return redirect()->route('admin.committee.index')->with('success', 'Committee member removed.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:committee_members,id',
            'items.*.display_order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            CommitteeMember::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return back()->with('success', 'Order updated.');
    }
}
