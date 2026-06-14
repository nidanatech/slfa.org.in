<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'message_type',
        'parent_id',
        'metadata',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TeamChannel::class, 'channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TeamMessage::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(TeamMessage::class, 'parent_id');
    }

    // Scopes
    public function scopeInChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper methods
    public function isOwnedBy($userId)
    {
        return $this->user_id == $userId;
    }

    public function markAsEdited()
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}
