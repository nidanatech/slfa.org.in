<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Contact;
use App\Models\Company;
use App\Models\User;
use App\Models\Tag;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Get filter parameters
        $pipelineId = $request->get('pipeline_id');
        $stageId = $request->get('stage_id');
        $status = $request->get('status');
        $ownerId = $request->get('owner_id');

        // Load opportunities with relationships including tags
        $opportunitiesQuery = Opportunity::with(['contact', 'contact.events', 'company', 'owner', 'stage', 'pipeline', 'tags', 'notes.author', 'tasks.owner'])
            ->forTenant($tenantId);

        if ($pipelineId) {
            $opportunitiesQuery->where('pipeline_id', $pipelineId);
        }
        if ($stageId) {
            $opportunitiesQuery->where('stage_id', $stageId);
        }
        if ($status) {
            $opportunitiesQuery->where('status', $status);
        }
        if ($ownerId) {
            $opportunitiesQuery->where('owner_id', $ownerId);
        }

        $opportunities = $opportunitiesQuery->get();

        // Load pipelines with stages for filters and board
        $pipelines = Pipeline::with('stages')
            ->forTenant($tenantId)
            ->get();

        // Load contacts and companies for form
        $contacts = Contact::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'emails', 'company_id')
            ->get();

        $companies = Company::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->get();

        // Load users for owner assignment
        $users = User::where('tenant_id', $tenantId)
            ->select('id', 'name', 'email')
            ->get();

        // Load all tags for the tenant
        $allTags = Tag::where('tenant_id', $tenantId)->get();

        // Load calendars for the tenant
        $calendars = Calendar::where('tenant_id', $tenantId)->get();

        return Inertia::render('Opportunities/Index', [
            'opportunities' => $opportunities,
            'pipelines' => $pipelines,
            'contacts' => $contacts,
            'companies' => $companies,
            'users' => $users,
            'allTags' => $allTags,
            'calendars' => $calendars,
            'filters' => [
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'status' => $status,
                'owner_id' => $ownerId,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'pipeline_id' => 'required|exists:pipelines,id',
            'stage_id' => 'required|exists:pipeline_stages,id',
            'contact_id' => 'required|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'owner_id' => 'nullable|exists:users,id',
            'value_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'expected_close_date' => 'nullable|date',
            'next_followup_date' => 'nullable|date',
            'status' => 'nullable|in:open,won,lost',
            'source' => 'nullable|in:chat,manual,api',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['status'] = $validated['status'] ?? 'open';
        $validated['source'] = $validated['source'] ?? 'manual';
        $validated['currency'] = $validated['currency'] ?? 'USD';

        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $opportunity = Opportunity::create($validated);

        // Sync tags
        if (!empty($tagIds)) {
            $opportunity->tags()->sync($tagIds);
        }

        return redirect()->route('opportunities.index')
            ->with('success', 'Opportunity created successfully.');
    }

    public function show(Opportunity $opportunity)
    {
        // Ensure tenant access
        if ($opportunity->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $opportunity->load([
            'pipeline.stages',
            'stage',
            'contact',
            'company',
            'owner',
            'tags',
            'notes.author',
            'stageHistory.fromStage',
            'stageHistory.toStage',
            'stageHistory.changedBy',
            'customFieldValues'
        ]);

        // Load activities related to this opportunity
        $activities = \App\Models\Activity::where('source_table', 'opportunities')
            ->where('source_id', $opportunity->id)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Opportunities/Show', [
            'opportunity' => $opportunity,
            'activities' => $activities,
        ]);
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        // Ensure tenant access
        if ($opportunity->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'pipeline_id' => 'sometimes|required|exists:pipelines,id',
            'stage_id' => 'sometimes|required|exists:pipeline_stages,id',
            'contact_id' => 'sometimes|required|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'owner_id' => 'nullable|exists:users,id',
            'value_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'expected_close_date' => 'nullable|date',
            'next_followup_date' => 'nullable|date',
            'status' => 'nullable|in:open,won,lost',
            'source' => 'nullable|in:chat,manual,api',
        ]);

        $opportunity->update($validated);

        return redirect()->back()
            ->with('success', 'Opportunity updated successfully.');
    }

    public function destroy(Opportunity $opportunity)
    {
        // Ensure tenant access
        if ($opportunity->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $opportunity->delete();

        return redirect()->route('opportunities.index')
            ->with('success', 'Opportunity deleted successfully.');
    }

    public function updateTags(Request $request, Opportunity $opportunity)
    {
        // Ensure tenant access
        if ($opportunity->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        // Sync tags
        $opportunity->tags()->sync($validated['tag_ids']);

        return redirect()->back()->with('success', 'Tags updated successfully.');
    }
}
