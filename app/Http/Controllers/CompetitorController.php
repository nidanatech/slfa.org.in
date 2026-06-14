<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\CompetitorSource;
use App\Services\WebScraperService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompetitorController extends Controller
{
    public function index()
    {
        $competitors = Competitor::forTenant(auth()->user()->tenant_id)
            ->with(['sources', 'creator'])
            ->withCount(['changes' => function($query) {
                $query->where('is_read', false);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $competitors->count(),
            'active' => $competitors->where('is_active', true)->count(),
            'unread_changes' => $competitors->sum('changes_count'),
        ];

        return Inertia::render('Marketing/CompetitorPulse/Index', [
            'competitors' => $competitors,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('Marketing/CompetitorPulse/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|string',
            'logo_url' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'monitoring_frequency' => 'required|in:hourly,daily,weekly',
            
            // Sources - more flexible validation
            'linkedin_url' => 'required|string',
            'twitter_url' => 'nullable|string',
            'instagram_url' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'blog_url' => 'nullable|string',
            'careers_url' => 'nullable|string',
            'app_store_url' => 'nullable|string',
            'play_store_url' => 'nullable|string',
        ]);

        $competitor = Competitor::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'website_url' => $validated['website_url'],
            'logo_url' => $validated['logo_url'],
            'industry' => $validated['industry'],
            'description' => $validated['description'],
            'is_active' => true,
            'monitoring_frequency' => $validated['monitoring_frequency'],
            'created_by' => auth()->id(),
        ]);

        // Create website source
        $this->createSource($competitor, 'website', null, $validated['website_url']);

        // Create social media sources
        if ($validated['linkedin_url']) {
            $this->createSource($competitor, 'social', 'linkedin', $validated['linkedin_url']);
        }
        if ($validated['twitter_url']) {
            $this->createSource($competitor, 'social', 'twitter', $validated['twitter_url']);
        }
        if ($validated['instagram_url']) {
            $this->createSource($competitor, 'social', 'instagram', $validated['instagram_url']);
        }
        if ($validated['youtube_url']) {
            $this->createSource($competitor, 'social', 'youtube', $validated['youtube_url']);
        }

        // Create other sources
        if ($validated['blog_url']) {
            $this->createSource($competitor, 'blog', null, $validated['blog_url']);
        }
        if ($validated['careers_url']) {
            $this->createSource($competitor, 'careers', null, $validated['careers_url']);
        }
        if ($validated['app_store_url']) {
            $this->createSource($competitor, 'product', 'app_store', $validated['app_store_url']);
        }
        if ($validated['play_store_url']) {
            $this->createSource($competitor, 'product', 'play_store', $validated['play_store_url']);
        }

        return redirect()->route('marketing.competitor-pulse.index')
            ->with('success', 'Competitor added successfully!');
    }

    public function show($id)
    {
        $competitor = Competitor::forTenant(auth()->user()->tenant_id)
            ->with(['sources', 'changes' => function($query) {
                $query->orderBy('detected_at', 'desc')->limit(20);
            }, 'insights' => function($query) {
                $query->where('is_dismissed', false)->orderBy('priority', 'desc')->limit(10);
            }])
            ->findOrFail($id);

        return Inertia::render('Marketing/CompetitorPulse/Show', [
            'competitor' => $competitor,
        ]);
    }

    public function edit($id)
    {
        $competitor = Competitor::forTenant(auth()->user()->tenant_id)
            ->with('sources')
            ->findOrFail($id);

        return Inertia::render('Marketing/CompetitorPulse/Edit', [
            'competitor' => $competitor,
        ]);
    }

    public function update(Request $request, $id)
    {
        $competitor = Competitor::forTenant(auth()->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|url',
            'logo_url' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'monitoring_frequency' => 'required|in:hourly,daily,weekly',
        ]);

        $competitor->update($validated);

        return redirect()->route('marketing.competitor-pulse.show', $competitor->id)
            ->with('success', 'Competitor updated successfully!');
    }

    public function destroy($id)
    {
        $competitor = Competitor::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        $competitor->delete();

        return redirect()->route('marketing.competitor-pulse.index')
            ->with('success', 'Competitor deleted successfully!');
    }

    public function monitor($id)
    {
        $competitor = Competitor::forTenant(auth()->user()->tenant_id)->findOrFail($id);

        // Dispatch monitoring job
        \App\Jobs\MonitorCompetitorJob::dispatch($competitor);

        return redirect()->back()
            ->with('success', 'Monitoring started for ' . $competitor->name);
    }

    // Helper method to create sources
    private function createSource(Competitor $competitor, string $type, ?string $platform, string $url): void
    {
        $scraper = new WebScraperService();
        $isValid = $scraper->validateUrl($url);

        $competitor->sources()->create([
            'source_type' => $type,
            'platform' => $platform,
            'url' => $url,
            'is_active' => true,
            'validation_status' => $isValid ? 'valid' : 'invalid',
            'validation_error' => $isValid ? null : 'URL validation failed',
            'scrape_frequency' => $competitor->monitoring_frequency,
        ]);
    }
}
