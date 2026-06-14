<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityCharter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ProjectPortalController extends Controller
{
    /**
     * Show the public-facing project portal using the magic link token.
     */
    public function showProjectDashboard($token)
    {
        $opportunity = Opportunity::where('portal_token', $token)
            ->with(['charter', 'tasks', 'invoices', 'contact', 'product', 'tenant', 'changeRequests'])
            ->firstOrFail();

        return Inertia::render('Portal/ProjectDashboard', [
            'opportunity' => $opportunity,
            'charter' => $opportunity->charter,
            'milestones' => $opportunity->tasks->groupBy('milestone_name'),
            'invoices' => $opportunity->invoices,
            'tenant' => $opportunity->tenant,
            'product' => $opportunity->product,
        ]);
    }

    /**
     * Submit a digital signature to approve the PMBOK charter.
     */
    public function signCharter(Request $request, $token)
    {
        $request->validate([
            'signature_name' => 'required|string|max:255',
        ]);

        $opportunity = Opportunity::where('portal_token', $token)->firstOrFail();
        $charter = $opportunity->charter;

        if (!$charter) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        if ($charter->is_signed) {
            return response()->json(['message' => 'Document is already signed.'], 400);
        }

        $charter->update([
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by_ip' => $request->ip(),
            'signed_device' => $request->header('Sec-Ch-Ua-Platform') ?? 'Unknown Device',
            'user_agent' => $request->userAgent(),
            'signature_data' => $request->signature_data, // Base64 from canvas
            'signature_hash' => hash('sha256', $request->signature_name . config('app.key') . now()),
        ]);

        // Move Opportunity to execution stage
        $opportunity->update(['status' => 'won']);

        // Trigger the service to generate WBS Tasks if not already generated
        $draftService = app(\App\Services\ProjectDraftingService::class);
        $draftService->generateWbs($opportunity);

        return redirect()->back()->with('success', 'Project Charter successfully signed and approved.');
    }

    /**
     * Submit a change request for a signed charter.
     */
    public function requestChange(Request $request, $token)
    {
        $request->validate([
            'requested_changes' => 'required|string|min:10',
            'justification' => 'required|string|min:10',
        ]);

        $opportunity = Opportunity::where('portal_token', $token)->firstOrFail();
        $charter = $opportunity->charter;

        if (!$charter || !$charter->is_signed) {
            return response()->json(['message' => 'Only signed projects can have change requests.'], 400);
        }

        \App\Models\ProjectChangeRequest::create([
            'tenant_id' => $opportunity->tenant_id,
            'opportunity_id' => $opportunity->id,
            'charter_id' => $charter->id,
            'requested_changes' => $request->requested_changes,
            'justification' => $request->justification,
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Your change request has been submitted to the Project Lead.');
    }

    /**
     * Generate Magic Link for the Opportunity
     */
    public static function generateMagicLink(Opportunity $opportunity)
    {
        if (!$opportunity->portal_token) {
            $opportunity->update(['portal_token' => Str::random(40)]);
        }
        return url('/portal/project/' . $opportunity->portal_token);
    }
}
