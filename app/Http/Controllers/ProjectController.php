<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\ProjectStakeholder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index()
    {
        // For simplicity, we treat Opportunities with a charter or specific pipeline stage as "Projects"
        // Let's grab all Opportunities that have an OpportunityCharter attached, OR we can just load them via scope.
        // We will assume any opportunity with is_project = true (if we add it) or just grabbing ones with Charters
        // For now, grabbing all opportunities with a charter attached to explicitly show as PMBOK projects.
        
        $tenantId = auth()->user()->tenant_id;
        
        $projects = Opportunity::where('tenant_id', $tenantId)
            ->has('charter')
            ->with(['contact', 'charter', 'product', 'changeRequests'])
            ->latest()
            ->paginate(15);
            
        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'contacts' => \App\Models\Contact::where('tenant_id', $tenantId)->get(['id', 'name', 'emails']),
            'products' => \App\Models\Product::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->first();
        $businessCurrency = $businessProfile?->currency ?? 'USD';

        return Inertia::render('Projects/Create', [
            'contacts' => \App\Models\Contact::where('tenant_id', auth()->user()->tenant_id)->get(['id', 'name', 'emails']),
            'products' => \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)->get(['id', 'name']),
            'project' => null,
            'businessCurrency' => $businessCurrency
        ]);
    }

    /**
     * Show the project management dashboard for a signed/active project.
     */
    public function show(Opportunity $project)
    {
        // Ensure PMBOK steps exist
        $project->syncTimelineSteps();

        $project->load([
            'contact', 
            'product', 
            'charter', 
            'charterVersions', 
            'changeRequests', 
            'invoices', 
            'stage',
            'timelineSteps',
            'artifacts',
            'stakeholders.contact'
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'changeRequests' => $project->changeRequests,
            'charterVersions' => $project->charterVersions()->orderBy('created_at', 'desc')->get(),
            'invoices' => $project->invoices,
            'timelineSteps' => $project->timelineSteps,
            'stakeholders' => $project->stakeholders,
            'tenantContacts' => Contact::where('tenant_id', auth()->user()->tenant_id)->get(['id', 'name', 'emails', 'phones']),
            'businessProfile' => \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->first()
        ]);
    }

    /**
     * Show the project creation wizard or management view.
     */
    public function edit(Opportunity $project)
    {
        $project->load(['charter', 'contact', 'product']);

        // If project is already signed and the user is NOT coming here to REVise (via a flag), 
        // they should see the Show view.
        if ($project->charter?->is_signed && !request()->has('revise')) {
            return redirect()->route('projects.show', $project);
        }

        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->first();
        $businessCurrency = $businessProfile?->currency ?? 'USD';

        return Inertia::render('Projects/Create', [
            'contacts' => \App\Models\Contact::where('tenant_id', auth()->user()->tenant_id)->get(['id', 'name', 'emails']),
            'products' => \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)->get(['id', 'name']),
            'project' => $project,
            'changeRequests' => $project->changeRequests()->where('status', 'pending')->get(),
            'charterVersions' => $project->charterVersions()->orderBy('created_at', 'desc')->get(),
            'businessCurrency' => $businessCurrency
        ]);
    }

    /**
     * Fetch conversation history for given contacts to seed the AI wizard.
     */
    public function fetchContext(Request $request)
    {
        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        $conversations = \App\Models\Conversation::whereIn('contact_id', $request->contact_ids)
            ->with(['messages' => function($q) {
                $q->orderBy('created_at', 'desc')->take(30);
            }])
            ->get();

        $contextText = "";
        foreach ($conversations as $conv) {
            $messages = $conv->messages->reverse();
            foreach ($messages as $msg) {
                $sender = $msg->is_from_contact ? 'Client' : 'Freelancer';
                $contextText .= "{$sender}: {$msg->body}\n";
            }
            $contextText .= "\n--- End of Thread ---\n\n";
        }

        return response()->json(['context' => $contextText]);
    }

    /**
     * Generate PMBOK draft using OpenAI based on wizard questionnaire answers.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'goals' => 'required|string',
            'product_id' => 'nullable|exists:products,id'
        ]);

        $productContext = "Generic Freelance Project";
        if ($request->product_id) {
            $product = \App\Models\Product::find($request->product_id);
            $productContext = "Product/Service Type: {$product->name}\nDescription: {$product->description}";
        }

        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->first();
        $businessCurrency = $businessProfile?->currency ?? 'USD';

        $systemPrompt = "You are an elite Business Consultant and Strategy Expert.
Based on the following client goals and service context, draft a high-fidelity, EXTENSIVE Project Authorization Document.
IMPORTANT:
1. DO NOT mention 'PMBOK' or technical project management jargon.
2. Refer to the 'Project Manager' as 'Business Lead' or use the company profile.
3. 'In Scope' and 'Out of Scope' MUST be presented as extensive, detailed, numbered lists. Each item SHOULD be on a NEW LINE with a newline character between them.
4. DO NOT provide lists as a single paragraph. Use a vertical list format.
5. Be descriptive, professional, and detailed for each section.

The output MUST be a valid JSON object with exactly these fields:
{
    \"purpose_justification\": \"Detailed explanation of business need, strategic alignment, and the specific problem we are solving.\",
    \"objectives\": \"Extensive measurable goals with detailed Success Criteria (KPIs).\",
    \"high_level_requirements\": \"Comprehensive list of essential features, technical needs, and stakeholder expectations.\",
    \"description\": \"Professional multi-paragraph summary of the project vision and execution boundary.\",
    \"deliverables\": \"Categorized list of outputs. Use newlines between items.\",
    \"in_scope\": \"Extensive numbered list of everything included. Use a NEW LINE for each item.\",
    \"out_of_scope\": \"Extensive numbered list of explicit exclusions. Use a NEW LINE for each item.\",
    \"overall_risk\": \"In-depth analysis of primary threats, market risks, or technical hurdles.\",
    \"milestone_summary\": \"List of major phases with estimated week numbers.\",
    \"budget_summary\": \"Professional financial breakdown summary or resource requirements.\",
    \"stakeholder_list\": \"Who is involved and their role/impact.\",
    \"approval_requirements\": \"Definition of 'Done' and who has decision authority.\",
    \"pm_authority\": \"Authority level of the Business Lead.\",
    \"sponsor_authority\": \"The client sponsor's role and their ultimate authority level.\",
    \"estimated_duration_weeks\": \"integer value\",
    \"budget_amount\": \"float value\",
    \"currency\": \"{$businessCurrency}\",
    \"estimated_start_date\": \"YYYY-MM-DD or null\",
    \"estimated_end_date\": \"YYYY-MM-DD or null\",
    \"assumptions\": \"List of critical assumptions.\",
    \"constraints\": \"Real-world constraints.\"
}";

        $systemPrompt .= "\nEnsure the tone is executive and authoritative.\nContext: {$productContext}";

        $aiService = app(\App\Services\AiService::class);
        $response = $aiService->generateText("Client Goals/Notes:\n\n{$request->goals}", $systemPrompt, 1500);
        $cleanJson = str_replace(['```json', '```'], '', $response);
        
        return response()->json(json_decode(trim($cleanJson), true) ?? []);
    }

    /**
     * Store a newly created manually defined project.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_id' => 'required|exists:contacts,id',
            'product_id' => 'nullable|exists:products,id',
            'description' => 'nullable|string',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Fetch/Create default pipeline (reuse existing logic from your store)
        $pipeline = \App\Models\Pipeline::where('tenant_id', $tenantId)->first();
        if (!$pipeline) {
            $pipeline = \App\Models\Pipeline::create([
                'tenant_id' => $tenantId,
                'name' => 'Main Projects Pipeline',
            ]);
        }

        $stage = $pipeline->stages()->orderBy('order_index')->first();
        if (!$stage) {
            $stage = $pipeline->stages()->create([
                'tenant_id' => $tenantId,
                'name' => 'Project Initiation',
                'order' => 0
            ]);
        }

        $contact = \App\Models\Contact::findOrFail($request->contact_id);
        
        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', $tenantId)->first();
        $businessCurrency = $businessProfile?->currency ?? 'USD';

        // Ensure contact is marked as a customer
        $currentTypes = $contact->contact_types ?? [];
        if (!in_array('customer', $currentTypes)) {
            $currentTypes[] = 'customer';
            $contact->update(['contact_types' => $currentTypes]);
        }

        // Create Customer record if it doesn't exist
        if (!$contact->customer) {
            \App\Models\Customer::create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'currency' => $businessCurrency
            ]);
        }

        $opportunity = Opportunity::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'contact_id' => $request->contact_id,
            'product_id' => $request->product_id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => auth()->id(),
            'status' => 'won',
            'currency' => $businessCurrency,
            'portal_token' => Str::random(40)
        ]);

        // Initialize empty charter
        $opportunity->charter()->create([
            'objectives' => $request->description ?? 'Project initialized.',
            'currency' => $businessCurrency
        ]);

        return redirect()->route('projects.show', $opportunity)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Update the charter details.
     */
    /**
     * Store a manual change request initiated by the Business Lead.
     */
    public function manualChange(Request $request, Opportunity $project)
    {
        $request->validate([
            'change_summary' => 'required|string|min:5',
        ]);

        $charter = $project->charter;

        if (!$charter) {
            return back()->with('error', 'Project charter not found.');
        }

        \App\Models\ProjectChangeRequest::create([
            'tenant_id' => auth()->user()->tenant_id,
            'opportunity_id' => $project->id,
            'charter_id' => $charter->id,
            'requested_changes' => $request->change_summary,
            'justification' => 'Manually initiated by Project Lead.',
            'status' => 'pending'
        ]);

        return back()->with('success', 'Manual change request recorded.');
    }

    public function update(Request $request, Opportunity $project)
    {
        Log::info('Project Update Request Received', [
            'project_id' => $project->id,
            'public_id' => $project->public_id,
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'change_summary' => 'nullable|string', // Optional note for this version
        ]);

        $project->update(['name' => $request->name]);
        
        $currentCharter = $project->charter;
        $newData = $request->only([
            'purpose_justification', 'objectives', 'high_level_requirements', 'description', 
            'deliverables', 'in_scope', 'out_of_scope', 'overall_risk', 'milestone_summary', 
            'budget_summary', 'stakeholder_list', 'approval_requirements', 'pm_authority', 
            'sponsor_authority', 'estimated_duration_weeks', 'budget_amount', 'currency', 
            'estimated_start_date', 'estimated_end_date', 'assumptions', 'constraints'
        ]);

        if ($currentCharter && $currentCharter->is_signed) {
            // If it's signed, we MUST create a new version
            $oldVersionParts = explode('.', $currentCharter->version_number);
            $major = $oldVersionParts[0] ?? 1;
            $minor = (int)($oldVersionParts[1] ?? 0) + 1;
            $newVersion = "$major.$minor";

            // Mark old as NOT current
            $currentCharter->update(['is_current' => false]);

            // Create new version
            $project->charter()->create(array_merge($newData, [
                'version_number' => $newVersion,
                'is_current' => true,
                'supersedes_id' => $currentCharter->id,
                'change_summary' => $request->change_summary ?? 'Client requested modifications.',
                'is_signed' => false, // New version needs new signature
                'currency' => $request->currency ?? $currentCharter->currency
            ]));

            // Mark any pending change requests as processed
            $project->changeRequests()->where('status', 'pending')->update([
                'status' => 'processed',
                'processed_at' => now()
            ]);

            return back()->with('success', "New version $newVersion created and sent for review.");
        } else {
            // Standard update for draft or if charter missing
            $project->charter()->updateOrCreate(
                ['opportunity_id' => $project->id, 'is_current' => true],
                $newData
            );

            return back()->with('success', 'Project updated successfully.');
        }
    }

    /**
     * Store a project artifact (AI content + Form data).
     */
    public function storeArtifact(Request $request, Opportunity $project)
    {
        $validated = $request->validate([
            'step_key' => 'required|string',
            'artifact_type' => 'required|string',
            'form_data' => 'nullable|array',
            'generated_content' => 'nullable|string',
        ]);

        $project->artifacts()->create([
            'step_key' => $validated['step_key'],
            'artifact_type' => $validated['artifact_type'],
            'form_data' => $validated['form_data'],
            'generated_content' => $validated['generated_content'],
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artifact stored successfully for reference.'
        ]);
    }

    /**
     * Mark a timeline step as completed.
     */
    public function completeStep(Request $request, Opportunity $project, $stepKey)
    {
        $step = $project->timelineSteps()->where('key', $stepKey)->first();
        
        if ($step) {
            $step->update([
                'status' => 'complete',
                'completed_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Step '{$stepKey}' marked as complete."
        ]);
    }

    /**
     * Store or link a stakeholder to the project.
     */
    public function addStakeholder(Request $request, Opportunity $project)
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|exists:contacts,id',
            'name' => 'required_without:contact_id|string|max:255',
            'email' => 'required_without:contact_id|email|max:255',
            'role' => 'nullable|string|max:255',
            'role_type' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $tenantId = auth()->user()->tenant_id;
        $contactId = $validated['contact_id'];

        Log::info('Stakeholder registration attempt', [
            'project_id' => $project->id,
            'contact_id' => $contactId,
            'email' => $validated['email'] ?? null,
            'role_type' => $validated['role_type']
        ]);

        try {
            return DB::transaction(function() use ($validated, $tenantId, $contactId, $project) {
                if (!$contactId) {
                    // Create new contact if it doesn't exist
                    $existingContact = Contact::where('tenant_id', $tenantId)
                        ->whereJsonContains('emails', [['value' => $validated['email']]])
                        ->orWhereJsonContains('emails', ['value' => $validated['email']])
                        ->first();

                    if ($existingContact) {
                        $contactId = $existingContact->id;
                    } else {
                        $contact = Contact::create([
                            'tenant_id' => $tenantId,
                            'name' => $validated['name'],
                            'emails' => [['value' => $validated['email'], 'type' => 'work']],
                            'contact_types' => ['stakeholder']
                        ]);
                        $contactId = $contact->id;
                    }
                } else {
                    // Handle existing contact email update if missing
                    $contact = Contact::find($contactId);
                    if (!$contact) throw new \Exception('Contact not found: ' . $contactId);

                    $existingEmails = $contact->emails ?? [];
                    $hasEmail = count($existingEmails) > 0;

                    if (!$hasEmail && !empty($validated['email'])) {
                        $contact->update([
                            'emails' => [['value' => $validated['email'], 'type' => 'work']]
                        ]);
                    }
                }

                Log::info('Updating/Creating Project Stakeholder', ['contact_id' => $contactId, 'project_id' => $project->id]);

                $project->stakeholders()->updateOrCreate(
                    ['contact_id' => $contactId],
                    [
                        'tenant_id' => $tenantId,
                        'role' => $validated['role'] ?? 'Contributor',
                        'role_type' => $validated['role_type'],
                        'notes' => $validated['notes']
                    ]
                );

                // Also add the engagement note as a permanent Note on the Contact record
                if (!empty($validated['notes'])) {
                    \App\Models\Note::create([
                        'tenant_id' => $tenantId,
                        'object_type' => 'contact',
                        'object_id' => $contactId,
                        'author_id' => auth()->id(),
                        'content' => "Project Involvement [{$project->name}]: {$validated['notes']}",
                        'visibility' => 'internal',
                        'source' => 'system'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'stakeholders' => $project->stakeholders()->with('contact')->get()
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Stakeholder registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update influence and interest for a stakeholder.
     */
    public function updateStakeholderMatrix(Request $request, Opportunity $project, ProjectStakeholder $stakeholder)
    {
        $validated = $request->validate([
            'influence' => 'required|integer|min:1|max:10',
            'interest' => 'required|integer|min:1|max:10',
            'strategy' => 'nullable|string'
        ]);

        // Map classification based on standard matrix
        // Interest (X) vs Influence (Y)
        $classification = 'Monitor (Minimum Effort)';
        if ($validated['influence'] > 5 && $validated['interest'] > 5) $classification = 'Manage Closely (Key Player)';
        elseif ($validated['influence'] > 5 && $validated['interest'] <= 5) $classification = 'Keep Satisfied';
        elseif ($validated['influence'] <= 5 && $validated['interest'] > 5) $classification = 'Keep Informed';

        $stakeholder->update([
            'influence' => $validated['influence'],
            'interest' => $validated['interest'],
            'strategy' => $validated['strategy'],
            'classification' => $classification
        ]);

        return response()->json([
            'success' => true,
            'stakeholders' => $project->stakeholders()->with('contact')->get()
        ]);
    }

    public function removeStakeholder(Opportunity $project, ProjectStakeholder $stakeholder)
    {
        if ($stakeholder->opportunity_id !== $project->id) {
            return response()->json(['success' => false, 'message' => 'Stakeholder does not belong to this project.'], 403);
        }

        $stakeholder->delete();

        return response()->json([
            'success' => true,
            'stakeholders' => $project->stakeholders()->with('contact')->get()
        ]);
    }

    /**
     * Update multiple stakeholders in batch (Strategies/Power/Interest).
     */
    public function batchUpdateStakeholders(Request $request, Opportunity $project)
    {
        $validated = $request->validate([
            'strategies' => 'required|array',
            'strategies.*.power' => 'required|string',
            'strategies.*.interest' => 'required|string',
            'strategies.*.strategy' => 'required|string'
        ]);

        foreach ($validated['strategies'] as $id => $data) {
            $stakeholder = $project->stakeholders()->find($id);
            if ($stakeholder) {
                $powerMap = ['Low' => 3, 'Medium' => 6, 'High' => 9];
                $interestMap = ['Low' => 3, 'Medium' => 6, 'High' => 9];

                $powerVal = $powerMap[$data['power']] ?? 5;
                $interestVal = $interestMap[$data['interest']] ?? 5;

                $classification = 'Monitor (Minimum Effort)';
                if ($powerVal > 5 && $interestVal > 5) $classification = 'Manage Closely (Key Player)';
                elseif ($powerVal > 5 && $interestVal <= 5) $classification = 'Keep Satisfied';
                elseif ($powerVal <= 5 && $interestVal > 5) $classification = 'Keep Informed';

                $stakeholder->update([
                    'influence' => $powerVal,
                    'interest' => $interestVal,
                    'strategy' => $data['strategy'],
                    'classification' => $classification
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'stakeholders' => $project->stakeholders()->with('contact')->get()
        ]);
    }
}
