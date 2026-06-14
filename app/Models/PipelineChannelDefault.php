<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineChannelDefault extends Model
{
    protected $fillable = [
        'pipeline_id',
        'channel',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}
