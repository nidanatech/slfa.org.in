<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class ProjectTimelineStep extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'opportunity_id',
        'phase',
        'key',
        'label',
        'description',
        'status',
        'order_position',
        'action_route',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }
}
