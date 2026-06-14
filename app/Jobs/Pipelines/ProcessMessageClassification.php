<?php

namespace App\Jobs\Pipelines;

use App\Models\Conversation;
use App\Services\Pipelines\AiClassificationService;
use App\Services\Pipelines\ContactStageService;
use App\Services\Pipelines\StageMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMessageClassification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conversationId;

    public function __construct($conversationId)
    {
        $this->conversationId = $conversationId;
    }

    public function handle(
        AiClassificationService $aiService,
        StageMappingService $mappingService,
        ContactStageService $stageService
    )
    {
        Log::info("Processing AI Classification for conversation {$this->conversationId}");

        $conversation = Conversation::with(['contact.pipeline', 'contact.stage', 'tenant'])->find($this->conversationId);

        if (!$conversation || !$conversation->contact) {
            Log::warning("AI Classification skipped: Conversation or Contact not found for ID {$this->conversationId}");
            return;
        }

        $contact = $conversation->contact;
        $pipeline = $contact->pipeline;

        // Fallback: If contact has no pipeline, look for recent open opportunity
        if (!$pipeline) {
            Log::info("Contact has no pipeline, searching for active opportunity", ['contact_id' => $contact->id]);
            $opportunity = \App\Models\Opportunity::where('contact_id', $contact->id)
                ->where('status', 'open')
                ->latest()
                ->first();
            
            if ($opportunity) {
                $pipeline = $opportunity->pipeline;
                Log::info("Found pipeline from opportunity", ['pipeline_id' => $pipeline->id ?? 'unknown']);
            }
        }

        if (!$pipeline) {
            Log::warning("AI Classification skipped: No pipeline associated with contact or open opportunity", ['contact_id' => $contact->id]);
            return;
        }

        if (!$pipeline->ai_sorting_enabled) {
            Log::info("AI Classification skipped: Pipeline '{$pipeline->name}' (ID: {$pipeline->id}) does not have AI sorting enabled.");
            return;
        }

        // Perform Classification
        Log::info("Classifying conversation Content...");
        $aiOutput = $aiService->classify($conversation);

        if (!$aiOutput || !isset($aiOutput['confidence'])) {
            Log::warning("AI Classification returned invalid output or failed for conversation {$this->conversationId}");
            return;
        }

        Log::info("AI Classification Result", ['output' => $aiOutput]);

        // Determine Target Stage
        $targetStage = $mappingService->determineTargetStage($pipeline, $aiOutput);

        if ($targetStage) {
            Log::info("Target stage determined: {$targetStage->name} (ID: {$targetStage->id})");
            // Attempt Update
            $stageService->attemptStageUpdate(
                $contact,
                $targetStage,
                (float) $aiOutput['confidence'],
                $aiOutput['reason'] ?? 'AI Classification'
            );
        } else {
            Log::info("No matching stage rule found for AI output in pipeline {$pipeline->id}");
        }

        // Update Conversation Stats
        $conversation->last_classified_at = now();
        $conversation->save();
    }
}
