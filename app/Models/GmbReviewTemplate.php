<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmbReviewTemplate extends Model
{
    protected $fillable = [
        'channel_id',
        'name',
        'star_rating',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'star_rating' => 'integer',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
