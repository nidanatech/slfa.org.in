<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GmbQuestion extends Model
{
    protected $fillable = [
        'channel_id',
        'question_id',
        'author_display_name',
        'author_profile_photo_url',
        'text',
        'upvote_count',
        'answer_text',
        'answer_author_display_name',
        'answer_create_time',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'answer_create_time' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isAnswered(): bool
    {
        return !empty($this->answer_text);
    }

    public function scopeUnanswered(Builder $query): Builder
    {
        return $query->whereNull('answer_text');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('create_time', 'desc');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('upvote_count', 'desc');
    }
}
