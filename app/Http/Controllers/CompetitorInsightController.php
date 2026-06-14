<?php

namespace App\Http\Controllers;

use App\Models\CompetitorInsight;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompetitorInsightController extends Controller
{
    public function index(Request $request)
    {
        $query = CompetitorInsight::forTenant(auth()->user()->tenant_id)
            ->with(['competitor', 'change'])
            ->active();

        // Filters
        if ($request->has('competitor_id')) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('type')) {
            $query->where('insight_type', $request->type);
        }

        $insights = $query->orderBy('priority', 'desc')
            ->orderBy('generated_at', 'desc')
            ->paginate(20);

        $competitors = \App\Models\Competitor::forTenant(auth()->user()->tenant_id)
            ->active()
            ->get(['id', 'name']);

        return Inertia::render('Marketing/CompetitorPulse/Insights', [
            'insights' => $insights,
            'competitors' => $competitors,
            'filters' => $request->only(['competitor_id', 'priority', 'type']),
        ]);
    }

    public function dismiss($id)
    {
        $insight = CompetitorInsight::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        $insight->dismiss(auth()->user());

        return redirect()->back()->with('success', 'Insight dismissed');
    }
}
