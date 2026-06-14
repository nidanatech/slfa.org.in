<?php

namespace App\Observers;

use App\Models\Note;

class NoteObserver
{
    /**
     * Handle the Note "created" event.
     */
    public function created(Note $note): void
    {
        // Only log if the note is attached to a contact
        if ($note->object_type === 'contact') {
             \App\Models\Activity::create([
                'tenant_id' => $note->tenant_id,
                'contact_id' => $note->object_id,
                'activity_type' => 'note_added',
                'source_table' => 'notes',
                'source_id' => $note->id,
                'summary' => 'Internal note added',
                'actor_id' => $note->author_id,
            ]);
        }
    }

    /**
     * Handle the Note "updated" event.
     */
    public function updated(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "deleted" event.
     */
    public function deleted(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "restored" event.
     */
    public function restored(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "force deleted" event.
     */
    public function forceDeleted(Note $note): void
    {
        //
    }
}
