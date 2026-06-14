<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStageRule extends Model
{
    protected $fillable = [
        'pipeline_id',
        'conditions_json',
        'target_stage_id',
        'priority',
    ];

    protected $casts = [
        'conditions_json' => 'array',
        'priority' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function targetStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'target_stage_id');
    }
}
