<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
         if ($task->object_type === 'contact') {
             \App\Models\Activity::create([
                'tenant_id' => $task->tenant_id,
                'contact_id' => $task->object_id,
                'activity_type' => 'task_created',
                'source_table' => 'tasks',
                'source_id' => $task->id,
                'summary' => "Task created: {$task->title}",
                'actor_id' => $task->created_by,
            ]);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        if ($task->object_type === 'contact') {
             // Check if status changed to completed
            if ($task->wasChanged('status') && $task->status === 'completed') {
                  \App\Models\Activity::create([
                    'tenant_id' => $task->tenant_id,
                    'contact_id' => $task->object_id,
                    'activity_type' => 'task_max_completed',
                    'source_table' => 'tasks',
                    'source_id' => $task->id,
                    'summary' => "Task completed: {$task->title}",
                    'actor_id' => auth()->id() ?? $task->owner_id, // Best guess for actor
                ]);
            }
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
