<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelParticipant extends Model
{
    protected $fillable = [
        'channel_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
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

    // Helper methods
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function updateLastRead()
    {
        $this->update(['last_read_at' => now()]);
    }
}
