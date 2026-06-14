<?php

namespace App\Listeners;

use App\Events\ContactCreated; // Assuming this exists or I'll stub it
use App\Events\MessageReceived;
use App\Jobs\Pipelines\ProcessMessageClassification;
use App\Models\Contact;
use App\Services\Pipelines\PipelineRoutingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PipelineEventListener
{
    protected $routingService;

    public function __construct(PipelineRoutingService $routingService)
    {
        $this->routingService = $routingService;
    }

    public function handleContactCreated($event)
    {
        if ($event->contact instanceof Contact) {
            $this->routingService->assignPipelineToContact($event->contact);
        }
    }

    public function handleMessageReceived(MessageReceived $event)
    {
        $message = $event->message;
        
        if ($message->direction !== 'inbound') {
            return;
        }

        $conversation = $message->conversation;
        if (!$conversation) {
            return;
        }

        $conversation->increment('message_count');

        // Check Trigger Criteria
        // 1. At least 2 inbound messages, last_classified_at is null
        // OR
        // 2. Now - last_classified_at > interval (e.g. 5 mins)
        
        $shouldClassify = false;
        
        // Count inbound messages logic could be complex, for now relying on total message_count heuristic or assuming implementation
        // For simplicity: Trigger every X messages or time interval
        
        if (!$conversation->last_classified_at && $conversation->message_count >= 2) {
             $shouldClassify = true;
        } elseif ($conversation->last_classified_at && $conversation->last_classified_at->diffInMinutes(now()) > 5) {
             $shouldClassify = true;
        }

        if ($shouldClassify) {
            ProcessMessageClassification::dispatch($conversation->id);
        }
    }
    
    // Wire up events in EventServiceProvider manually if needed, or rely on auto-discovery if named correctly.
    // For this implementation, the user likely needs to register these.
    // I'll assume usage of subscribe method style:
    
    public function subscribe($events)
    {
        // $events->listen(
        //     'App\Events\ContactCreated',
        //     [PipelineEventListener::class, 'handleContactCreated']
        // );

        $events->listen(
            MessageReceived::class,
            [PipelineEventListener::class, 'handleMessageReceived']
        );
    }
}
