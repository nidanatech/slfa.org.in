<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    protected $fillable = [
        'pipeline_id',
        'name',
        'order_index',
        'probability',
        'is_closed',
        'is_won',
        'is_final',
        'color',
        'description',
        'stage_type',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'probability' => 'integer',
        'is_closed' => 'boolean',
        'is_won' => 'boolean',
        'is_final' => 'boolean',
    ];

    // Relationships
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'stage_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }

    public function historyFrom(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'from_stage_id');
    }

    public function historyTo(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'to_stage_id');
    }

    // Scopes
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeWon($query)
    {
        return $query->where('is_won', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}
