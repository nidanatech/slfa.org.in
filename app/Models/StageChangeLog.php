<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class StageChangeLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'contact_id',
        'old_stage_id',
        'new_stage_id',
        'triggered_by',
        'confidence',
        'reason',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function oldStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'old_stage_id');
    }

    public function newStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'new_stage_id');
    }
}
