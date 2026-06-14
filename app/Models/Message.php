<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'conversation_id', 'direction', 'type', 'content', 'meta',
        'sender_type', 'delivered_at', 'seen_at', 'status', 'is_edited', 'edited_at'
    ];

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];
    
    public static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Always update the conversation's last_message_at timestamp
            if ($message->conversation) {
                $message->conversation->update([
                    'last_message_at' => $message->created_at ?? now()
                ]);
            }

            if ($message->direction === 'inbound') {
                $conversation = $message->conversation;
                if ($conversation && $conversation->tenant) {
                    $tenant = $conversation->tenant;
                    // Notify all users in the tenant
                    $users = $tenant->users;
                    foreach ($users as $user) {
                        $user->notify(new \App\Notifications\NewInboundMessageNotification($message));
                    }
                }
            }

            // Broadcast the message creation event for real-time updates
            broadcast(new \App\Events\MessageCreated($message))->toOthers();
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function tenant()
    {
        return $this->hasOneThrough(
            Tenant::class,
            Conversation::class,
            'id', // Foreign key on conversations table...
            'id', // Foreign key on tenants table...
            'conversation_id', // Local key on messages table...
            'tenant_id' // Local key on conversations table...
        );
    }
}
