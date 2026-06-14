<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamChannel extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'type',
        'created_by',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChannelParticipant::class, 'channel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TeamMessage::class, 'channel_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(TeamMessage::class, 'channel_id')->latestOfMany();
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    // Helper methods
    public function getUnreadCount($userId)
    {
        $participant = $this->participants()
            ->where('user_id', $userId)
            ->first();
        
        if (!$participant) return 0;
        
        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at ?? now()->subYears(10))
            ->count();
    }

    public function addParticipant($userId, $role = 'member')
    {
        return $this->participants()->create([
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function removeParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->delete();
    }

    public function isParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function markAsRead($userId)
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}
