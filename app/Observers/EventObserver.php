<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        // Fire system event
        event('event_scheduled', $event);

        // Log activity if related to a contact
        if ($event->contact_id) {
            Activity::create([
                'tenant_id' => $event->contact->tenant_id,
                'contact_id' => $event->contact_id,
                'activity_type' => 'event_scheduled',
                'source_table' => 'calendar_events',
                'source_id' => $event->id,
                'summary' => "Event scheduled: {$event->title}",
                'actor_id' => Auth::id() ?? $event->created_by,
            ]);
        }

        // Sync to Google Calendar if enabled
        if ($event->calendar?->googleConnection?->sync_enabled) {
            \App\Jobs\SyncEventToGoogle::dispatch($event, 'create');
        }
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        $original = $event->getOriginal();

        // Check for rescheduling
        if ($event->isDirty('start_datetime') || $event->isDirty('end_datetime')) {
            event('event_rescheduled', $event);

            if ($event->contact_id) {
                Activity::create([
                    'tenant_id' => $event->contact->tenant_id,
                    'contact_id' => $event->contact_id,
                    'activity_type' => 'event_rescheduled',
                    'source_table' => 'calendar_events',
                    'source_id' => $event->id,
                    'summary' => "Event rescheduled: {$event->title}",
                    'actor_id' => Auth::id(),
                ]);
            }
        }

        // Check for status changes
        if ($event->isDirty('status')) {
            if ($event->status === 'cancelled') {
                event('event_cancelled', $event);

                if ($event->contact_id) {
                    Activity::create([
                        'tenant_id' => $event->contact->tenant_id,
                        'contact_id' => $event->contact_id,
                        'activity_type' => 'event_cancelled',
                        'source_table' => 'calendar_events',
                        'source_id' => $event->id,
                        'summary' => "Event cancelled: {$event->title}",
                        'actor_id' => Auth::id(),
                    ]);
                }
            } elseif ($event->status === 'completed') {
                event('event_completed', $event);

                if ($event->contact_id) {
                    Activity::create([
                        'tenant_id' => $event->contact->tenant_id,
                        'contact_id' => $event->contact_id,
                        'activity_type' => 'event_completed',
                        'source_table' => 'calendar_events',
                        'source_id' => $event->id,
                        'summary' => "Event completed: {$event->title}",
                        'actor_id' => Auth::id(),
                    ]);
                }
            }
        }

        // Sync to Google Calendar if enabled (skip if only google fields changed)
        if ($event->calendar?->googleConnection?->sync_enabled && !$event->wasRecentlyCreated) {
            $googleFields = ['google_event_id', 'google_calendar_connection_id', 'last_synced_at'];
            $hasNonGoogleChanges = collect($event->getDirty())->keys()->diff($googleFields)->isNotEmpty();
            
            if ($hasNonGoogleChanges) {
                \App\Jobs\SyncEventToGoogle::dispatch($event, 'update');
            }
        }
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        if ($event->contact_id) {
            Activity::create([
                'tenant_id' => $event->contact->tenant_id,
                'contact_id' => $event->contact_id,
                'activity_type' => 'event_deleted',
                'source_table' => 'calendar_events',
                'source_id' => $event->id,
                'summary' => "Event deleted: {$event->title}",
                'actor_id' => Auth::id(),
            ]);
        }

        // Sync deletion to Google Calendar if it was synced
        if ($event->google_event_id && $event->googleConnection?->sync_enabled) {
            \App\Jobs\SyncEventToGoogle::dispatch($event, 'delete');
        }
    }
}
