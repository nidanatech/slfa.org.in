<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewInboundMessageNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $conversation;

    /**
     * Create a new notification instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
        $this->conversation = $message->conversation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For now, let's use database and broadcast (if configured)
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'sender_name' => $this->conversation->contact->name ?? 'Unknown',
            'message' => $this->message->content,
            'content' => $this->message->content, // Keep for backward compatibility if any
            'title' => 'New Message from ' . ($this->conversation->contact->name ?? 'Customer'),
            'type' => 'inbound_message',
            'action_url' => route('inbox.show', $this->conversation->id),
        ];
    }
}
