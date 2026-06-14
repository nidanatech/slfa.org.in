<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Conversation extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'contact_id', 'whatsapp_group_id', 'channel_id', 'status', 'assigned_to',
        'last_message_at', 'conversation_type', 'initiated_by',
        'next_followup_at', 'last_automated_followup_at', 'bot_disabled_until',
        'is_important', 'message_count', 'last_classified_at', 'classification_version'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'last_automated_followup_at' => 'datetime',
        'last_classified_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function whatsappGroup()
    {
        return $this->belongsTo(WhatsAppGroup::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'conversation_tag');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'conversation_user');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function activeBotSession()
    {
        return $this->hasOne(BotSession::class)->whereNull('ended_at');
    }

    public function referral()
    {
        return $this->hasOne(ConversationReferral::class);
    }

    // Scopes
    public function scopeInternal($query)
    {
        return $query->where('conversation_type', 'internal');
    }

    public function scopeExternal($query)
    {
        return $query->where('conversation_type', 'external');
    }

    // Helper methods
    public function isInternal()
    {
        return $this->conversation_type === 'internal';
    }

    public function isExternal()
    {
        return $this->conversation_type === 'external';
    }
}
