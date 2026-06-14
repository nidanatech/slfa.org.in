<?php

namespace App\Services\Pipelines;

use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\PipelineChannelDefault;

class PipelineRoutingService
{
    public function assignPipelineToContact(Contact $contact): void
    {
        // 1. Identify channel from source_channel logic
        $channelId = $contact->channel_id;
        $channelType = $contact->channel ? $contact->channel->type : null;
        
        if (!$channelId && !$channelType) {
             // Fallback if no channel info
             $this->assignDefaultPipeline($contact);
             return;
        }

        // 2. Query pipeline_channel_defaults
        // We match either the specific channel ID or the generic channel type
        $default = PipelineChannelDefault::where(function($q) use ($channelId, $channelType) {
                if ($channelId) $q->where('channel', (string)$channelId);
                if ($channelType) $q->orWhere('channel', $channelType);
            })
            ->whereHas('pipeline', function ($query) use ($contact) {
                $query->where('tenant_id', $contact->tenant_id)
                      ->where('is_active', true);
            })
            ->orderBy('priority', 'asc') // Lowest priority number wins
            ->first();

        if ($default) {
            $this->assignPipeline($contact, $default->pipeline);
            return;
        }

        // 3. Fallback to default pipeline
        $this->assignDefaultPipeline($contact);
    }

    protected function assignDefaultPipeline(Contact $contact): void
    {
        $defaultPipeline = Pipeline::where('tenant_id', $contact->tenant_id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultPipeline) {
            $this->assignPipeline($contact, $defaultPipeline);
        }
    }

    protected function assignPipeline(Contact $contact, Pipeline $pipeline): void
    {
        $contact->pipeline_id = $pipeline->id;
        
        // Assign initial stage (lowest weight)
        $initialStage = $pipeline->stages()->orderBy('order_index', 'asc')->first();
        if ($initialStage) {
            $contact->stage_id = $initialStage->id;
        }

        $contact->save();
    }
}
