<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminBroadcastController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/Broadcast/Create', [
            'cities' => Member::where('status', 'active')->whereNotNull('city')->distinct()->pluck('city')->sort()->values(),
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'audience' => 'required|string', // all, city, type
            'audience_value' => 'nullable|string',
        ]);

        $query = Member::where('status', 'active');
        
        if ($validated['audience'] === 'city') {
            $query->where('city', $validated['audience_value']);
        } elseif ($validated['audience'] === 'type') {
            $query->where('membership_type', $validated['audience_value']);
        }

        $members = $query->get();

        // TODO: Send email to $members->pluck('email')
        
        return redirect()->route('admin.dashboard')->with('success', 'Broadcast sent to ' . $members->count() . ' members.');
    }
}
