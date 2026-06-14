<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Pipeline extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'name',
        'is_default',
        'created_by',
        'ai_sorting_enabled',
        'ai_confidence_threshold',
        'allow_stage_downgrade',
        'description',
        'is_active',
        'ai_settings',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'ai_sorting_enabled' => 'boolean',
        'ai_confidence_threshold' => 'float',
        'allow_stage_downgrade' => 'boolean',
        'is_active' => 'boolean',
        'ai_settings' => 'array',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order_index');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function channelDefaults(): HasMany
    {
        return $this->hasMany(PipelineChannelDefault::class);
    }

    public function stageRules(): HasMany
    {
        return $this->hasMany(PipelineStageRule::class)->orderBy('priority', 'desc');
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForObjectType($query, $objectType = 'opportunity')
    {
        return $query->where('object_type', $objectType);
    }
}
