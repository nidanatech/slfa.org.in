<?php

namespace App\Observers;

use App\Models\Opportunity;

class OpportunityObserver
{
    /**
     * Handle the Opportunity "created" event.
     * Fires: opportunity_created
     */
    public function created(Opportunity $opportunity): void
    {
        // Log activity
        \App\Models\Activity::create([
            'tenant_id' => $opportunity->tenant_id,
            'contact_id' => $opportunity->contact_id,
            'activity_type' => 'opportunity_created',
            'source_table' => 'opportunities',
            'source_id' => $opportunity->id,
            'summary' => "Opportunity created: {$opportunity->name}",
            'actor_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        // Create initial stage history record
        \App\Models\OpportunityStageHistory::create([
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => null,
            'to_stage_id' => $opportunity->stage_id,
            'changed_by' => \Illuminate\Support\Facades\Auth::id(),
            'changed_at' => now(),
        ]);

        // Fire event for automation
        event('opportunity_created', $opportunity);
    }

    /**
     * Handle the Opportunity "updating" event.
     * Detects changes and fires appropriate events.
     */
    public function updating(Opportunity $opportunity): void
    {
        $original = $opportunity->getOriginal();

        // Detect stage change
        if ($opportunity->isDirty('stage_id')) {
            $fromStageId = $original['stage_id'];
            $toStageId = $opportunity->stage_id;

            // Log stage history
            \App\Models\OpportunityStageHistory::create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStageId,
                'changed_by' => \Illuminate\Support\Facades\Auth::id(),
                'changed_at' => now(),
            ]);

            // Log activity
            $fromStage = \App\Models\PipelineStage::find($fromStageId);
            $toStage = \App\Models\PipelineStage::find($toStageId);
            
            \App\Models\Activity::create([
                'tenant_id' => $opportunity->tenant_id,
                'contact_id' => $opportunity->contact_id,
                'activity_type' => 'stage_changed',
                'source_table' => 'opportunities',
                'source_id' => $opportunity->id,
                'summary' => "Stage changed from {$fromStage->name} to {$toStage->name}",
                'actor_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            // Fire event
            event('stage_changed', [$opportunity, $fromStageId, $toStageId]);
        }

        // Detect status change to won
        if ($opportunity->isDirty('status') && $opportunity->status === 'won') {
            $opportunity->closed_at = now();
            
            \App\Models\Activity::create([
                'tenant_id' => $opportunity->tenant_id,
                'contact_id' => $opportunity->contact_id,
                'activity_type' => 'opportunity_won',
                'source_table' => 'opportunities',
                'source_id' => $opportunity->id,
                'summary' => "Opportunity won: {$opportunity->name}",
                'actor_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            event('opportunity_won', $opportunity);
        }

        // Detect status change to lost
        if ($opportunity->isDirty('status') && $opportunity->status === 'lost') {
            $opportunity->closed_at = now();
            
            \App\Models\Activity::create([
                'tenant_id' => $opportunity->tenant_id,
                'contact_id' => $opportunity->contact_id,
                'activity_type' => 'opportunity_lost',
                'source_table' => 'opportunities',
                'source_id' => $opportunity->id,
                'summary' => "Opportunity lost: {$opportunity->name}",
                'actor_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            event('opportunity_lost', $opportunity);
        }

        // Detect owner change
        if ($opportunity->isDirty('owner_id')) {
            $oldOwner = \App\Models\User::find($original['owner_id']);
            $newOwner = \App\Models\User::find($opportunity->owner_id);
            
            \App\Models\Activity::create([
                'tenant_id' => $opportunity->tenant_id,
                'contact_id' => $opportunity->contact_id,
                'activity_type' => 'owner_changed',
                'source_table' => 'opportunities',
                'source_id' => $opportunity->id,
                'summary' => "Owner changed from {$oldOwner->name} to {$newOwner->name}",
                'actor_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            event('owner_changed', [$opportunity, $original['owner_id'], $opportunity->owner_id]);
        }

        // Detect company change and sync to contact
        if ($opportunity->isDirty('company_id') && $opportunity->contact_id) {
            \App\Models\Contact::where('id', $opportunity->contact_id)
                ->update(['company_id' => $opportunity->company_id]);
        }
    }

    /**
     * Handle the Opportunity "deleted" event.
     */
    public function deleted(Opportunity $opportunity): void
    {
        \App\Models\Activity::create([
            'tenant_id' => $opportunity->tenant_id,
            'contact_id' => $opportunity->contact_id,
            'activity_type' => 'opportunity_deleted',
            'source_table' => 'opportunities',
            'source_id' => $opportunity->id,
            'summary' => "Opportunity deleted: {$opportunity->name}",
            'actor_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);
    }
}
