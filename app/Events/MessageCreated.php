<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        
        // Load relationships needed for the frontend
        $this->message->load('conversation.contact', 'conversation.channel', 'conversation.assignee');
        $this->conversation = $this->message->conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast to the tenant's private channel
        return [
            new PrivateChannel('tenant.' . $this->conversation->tenant_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'direction' => $this->message->direction,
                'type' => $this->message->type,
                'content' => $this->message->content,
                'status' => $this->message->status,
                'created_at' => $this->message->created_at,
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'contact' => [
                    'id' => $this->conversation->contact->id,
                    'name' => $this->conversation->contact->name,
                    'avatar_url' => $this->conversation->contact->avatar_url,
                ],
                'channel' => [
                    'id' => $this->conversation->channel->id,
                    'type' => $this->conversation->channel->type,
                    'name' => $this->conversation->channel->name,
                    'deleted_at' => $this->conversation->channel->deleted_at,
                ],
                'assigned_to' => $this->conversation->assigned_to,
                'last_message_at' => $this->conversation->last_message_at,
                'status' => $this->conversation->status,
                'is_important' => $this->conversation->is_important,
            ],
        ];
    }
}
