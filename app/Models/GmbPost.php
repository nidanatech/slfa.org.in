<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GmbPost extends Model
{
    protected $fillable = [
        'channel_id',
        'post_id',
        'summary',
        'call_to_action_type',
        'call_to_action_url',
        'media_format',
        'media_url',
        'topic_type',
        'event_title',
        'event_schedule',
        'create_time',
        'update_time',
        'search_url',
        'state',
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'event_schedule' => 'array',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('state', 'LIVE');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('state', 'EXPIRED');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('topic_type', $type);
    }

    public function isEvent(): bool
    {
        return $this->topic_type === 'EVENT';
    }

    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }
}
