<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GmbReview extends Model
{
    protected $fillable = [
        'channel_id',
        'review_id',
        'reviewer_display_name',
        'reviewer_profile_photo_url',
        'star_rating',
        'comment',
        'review_reply',
        'reply_update_time',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'reply_update_time' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function hasReply(): bool
    {
        return !empty($this->review_reply);
    }

    public function scopeWithRating(Builder $query, int $rating): Builder
    {
        return $query->where('star_rating', $rating);
    }

    public function scopeWithoutReply(Builder $query): Builder
    {
        return $query->whereNull('review_reply');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('create_time', 'desc');
    }
}
