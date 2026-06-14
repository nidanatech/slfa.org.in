<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectArtifact extends Model
{
    protected $fillable = [
        'project_id',
        'step_key',
        'artifact_type',
        'form_data',
        'generated_content',
        'created_by'
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
