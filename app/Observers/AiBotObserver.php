<?php

namespace App\Observers;

use App\Models\AiBot;

class AiBotObserver
{
    /**
     * Handle the AiBot "created" event.
     */
    public function created(AiBot $aiBot): void
    {
        \App\Models\Activity::create([
            'tenant_id' => $aiBot->tenant_id,
            'contact_id' => null, // Optional for system events
            'activity_type' => 'bot_created',
            'source_table' => 'ai_bots',
            'source_id' => $aiBot->id,
            'summary' => "Bot '{$aiBot->name}' created",
            'actor_id' => auth()->id() ?? null,
        ]);
    }

    /**
     * Handle the AiBot "updated" event.
     */
    public function updated(AiBot $aiBot): void
    {
        if ($aiBot->isDirty('name')) {
            \App\Models\Activity::create([
                'tenant_id' => $aiBot->tenant_id,
                'contact_id' => null,
                'activity_type' => 'bot_updated',
                'source_table' => 'ai_bots',
                'source_id' => $aiBot->id,
                'summary' => "Bot name changed from '{$aiBot->getOriginal('name')}' to '{$aiBot->name}'",
                'actor_id' => auth()->id() ?? null,
            ]);
        }
    }

    /**
     * Handle the AiBot "deleted" event.
     */
    public function deleted(AiBot $aiBot): void
    {
        \App\Models\Activity::create([
            'tenant_id' => $aiBot->tenant_id,
            'contact_id' => null,
            'activity_type' => 'bot_deleted',
            'source_table' => 'ai_bots',
            'source_id' => $aiBot->id,
            'summary' => "Bot '{$aiBot->name}' deleted",
            'actor_id' => auth()->id() ?? null,
        ]);
    }

    /**
     * Handle the AiBot "restored" event.
     */
    public function restored(AiBot $aiBot): void
    {
        //
    }

    /**
     * Handle the AiBot "force deleted" event.
     */
    public function forceDeleted(AiBot $aiBot): void
    {
        //
    }
}
