<?php

namespace App\Services\Pipelines;

use App\Models\Contact;
use App\Models\PipelineStage;
use App\Models\StageChangeLog;
use Carbon\Carbon;
use App\Events\LeadStageChanged;

class ContactStageService
{
    public function attemptStageUpdate(Contact $contact, PipelineStage $targetStage, float $aiConfidence, string $reason): void
    {
        $pipeline = $contact->pipeline;
        if (!$pipeline) return;

        // Guardrail 1: Confidence Threshold
        if ($aiConfidence < $pipeline->ai_confidence_threshold) {
            return;
        }

        $currentStage = $contact->stage;
        
        // Guardrail 2: Downgrade Check
        if ($currentStage && $targetStage->order_index < $currentStage->order_index) {
            if (!$pipeline->allow_stage_downgrade) {
                // Potential check for "higher threshold" mentioned in prompt could go here
                // For now, strict adherence to the flag
                return;
            }
        }

        // Guardrail 3: Same Stage
        if ($currentStage && $currentStage->id === $targetStage->id) {
            return;
        }

        // Guardrail 4: Cooldown
        if ($contact->next_stage_change_allowed_at && now()->lt($contact->next_stage_change_allowed_at)) {
            return;
        }

        // Execute Update
        $this->updateStage($contact, $targetStage, 'ai', $aiConfidence, $reason);
    }

    public function updateStage(Contact $contact, PipelineStage $newStage, string $triggeredBy, ?float $confidence = null, ?string $reason = null): void
    {
        $oldStageId = $contact->stage_id;

        $contact->stage_id = $newStage->id;
        $contact->stage_confidence = $confidence;
        $contact->stage_updated_by = $triggeredBy;
        $contact->stage_updated_at = now();
        
        // Set cooldown - e.g. 30 minutes
        $contact->next_stage_change_allowed_at = now()->addMinutes(30);
        
        $contact->save();
        
        // Sync to Opportunities
        $existingOpps = \App\Models\Opportunity::where('contact_id', $contact->id)
            ->where('pipeline_id', $contact->pipeline_id)
            ->where('status', 'open')
            ->get();

        if ($existingOpps->isNotEmpty()) {
            foreach ($existingOpps as $opp) {
                $opp->update(['stage_id' => $newStage->id]);
            }
        } elseif ($triggeredBy === 'ai' && $contact->pipeline_id) {
            // Create Opportunity if none exists and triggered by AI
            $opp = \App\Models\Opportunity::create([
                'tenant_id' => $contact->tenant_id,
                'name' => $contact->name ?? 'New Lead',
                'pipeline_id' => $contact->pipeline_id,
                'stage_id' => $newStage->id,
                'contact_id' => $contact->id,
                'status' => 'open',
                'source' => 'chat',
                'value_amount' => 0,
            ]);

            // Add Audit Note
            \App\Models\Note::create([
                'tenant_id' => $contact->tenant_id,
                'object_type' => \App\Models\Opportunity::class,
                'object_id' => $opp->id,
                'content' => "Opportunity created automatically via AI Mapping Rules.\nReason: " . ($reason ?? 'AI Classification') . "\nConfidence: " . ($confidence ? number_format($confidence, 2) : 'N/A'),
                'source' => 'automation',
            ]);
        }

        // Log Change
        StageChangeLog::create([
            'contact_id' => $contact->id,
            'old_stage_id' => $oldStageId,
            'new_stage_id' => $newStage->id,
            'triggered_by' => $triggeredBy,
            'confidence' => $confidence,
            'reason' => $reason,
        ]);

        // Emit Event
        event(new LeadStageChanged($contact, $oldStageId, $newStage->id));
    }
}
