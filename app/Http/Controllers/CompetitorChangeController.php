<?php

namespace App\Http\Controllers;

use App\Models\CompetitorChange;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompetitorChangeController extends Controller
{
    public function index(Request $request)
    {
        $query = CompetitorChange::with(['competitor', 'source'])
            ->whereHas('competitor', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });

        // Filters
        if ($request->has('competitor_id')) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('type')) {
            $query->where('change_type', $request->type);
        }

        if ($request->has('unread_only') && $request->unread_only) {
            $query->unread();
        }

        $changes = $query->orderBy('detected_at', 'desc')
            ->paginate(20);

        $competitors = \App\Models\Competitor::forTenant(auth()->user()->tenant_id)
            ->active()
            ->get(['id', 'name']);

        return Inertia::render('Marketing/CompetitorPulse/Changes', [
            'changes' => $changes,
            'competitors' => $competitors,
            'filters' => $request->only(['competitor_id', 'severity', 'type', 'unread_only']),
        ]);
    }

    public function markAsRead($id)
    {
        $change = CompetitorChange::whereHas('competitor', function($q) {
            $q->where('tenant_id', auth()->user()->tenant_id);
        })->findOrFail($id);

        $change->markAsRead();

        return redirect()->back()->with('success', 'Change marked as read');
    }
}
