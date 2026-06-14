<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialPostChannel extends Model
{
    protected $fillable = [
        'social_post_id',
        'channel_id',
        'platform_post_id',
        'platform_url',
        'status',
        'published_at',
        'error_message',
        'platform_metadata',
    ];

    protected $casts = [
        'platform_metadata' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(SocialPostAnalytics::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper Methods
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsPublished(string $platformPostId, string $platformUrl = null): void
    {
        $this->update([
            'status' => 'published',
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl,
            'published_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
