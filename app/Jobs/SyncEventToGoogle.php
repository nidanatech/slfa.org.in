<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEventToGoogle implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $event,
        public string $action = 'create' // create, update, delete
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $googleService): void
    {
        // Check if event's calendar has Google sync enabled
        $connection = $this->event->calendar?->googleConnection;
        
        if (!$connection || !$connection->sync_enabled) {
            Log::info('Event calendar not synced to Google, skipping', [
                'event_id' => $this->event->id,
                'calendar_id' => $this->event->calendar_id
            ]);
            return;
        }

        try {
            switch ($this->action) {
                case 'create':
                    $googleEventId = $googleService->createEvent($connection, $this->event);
                    $this->event->update([
                        'google_event_id' => $googleEventId,
                        'google_calendar_connection_id' => $connection->id,
                        'last_synced_at' => now(),
                    ]);
                    break;

                case 'update':
                    if ($this->event->google_event_id) {
                        $googleService->updateEvent($connection, $this->event);
                        $this->event->update(['last_synced_at' => now()]);
                    } else {
                        // Event not yet synced, create it
                        $googleEventId = $googleService->createEvent($connection, $this->event);
                        $this->event->update([
                            'google_event_id' => $googleEventId,
                            'google_calendar_connection_id' => $connection->id,
                            'last_synced_at' => now(),
                        ]);
                    }
                    break;

                case 'delete':
                    if ($this->event->google_event_id) {
                        $googleService->deleteEvent($connection, $this->event->google_event_id);
                    }
                    break;
            }

            // Clear any previous sync errors
            $connection->update(['sync_errors' => null]);
            
        } catch (\Exception $e) {
            Log::error('Google Calendar sync failed', [
                'event_id' => $this->event->id,
                'action' => $this->action,
                'error' => $e->getMessage()
            ]);

            // Store error in connection
            $errors = $connection->sync_errors ?? [];
            $errors[] = [
                'event_id' => $this->event->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
            $connection->update(['sync_errors' => array_slice($errors, -10)]); // Keep last 10 errors

            throw $e; // Re-throw to trigger retry
        }
    }
}
