<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbInsight extends Model
{
    protected $fillable = [
        'channel_id',
        'metric_date',
        'views_search',
        'views_maps',
        'views_total',
        'actions_website',
        'actions_phone',
        'actions_driving_directions',
        'photos_views_merchant',
        'photos_views_customers',
        'photos_count_merchant',
        'photos_count_customers',
        'local_post_views_search',
        'local_post_actions_call_to_action',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function getTotalActionsAttribute(): int
    {
        return $this->actions_website + $this->actions_phone + $this->actions_driving_directions;
    }

    public function getTotalPhotoViewsAttribute(): int
    {
        return $this->photos_views_merchant + $this->photos_views_customers;
    }
}
