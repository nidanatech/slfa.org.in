<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class CompetitorInsight extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'competitor_id',
        'change_id',
        'insight_type',
        'priority',
        'title',
        'summary',
        'analysis',
        'recommendations',
        'affected_areas',
        'confidence_score',
        'generated_by',
        'generated_at',
        'is_dismissed',
        'dismissed_at',
        'dismissed_by',
    ];

    protected $casts = [
        'recommendations' => 'array',
        'affected_areas' => 'array',
        'confidence_score' => 'decimal:2',
        'generated_at' => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
    ];

    // Relationships
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function change(): BelongsTo
    {
        return $this->belongsTo(CompetitorChange::class, 'change_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CompetitorAlert::class, 'insight_id');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function dismiss(User $user): void
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'dismissed_by' => $user->id,
        ]);
    }

    public function createAlert($type, $recipientId = null): void
    {
        $this->alerts()->create([
            'tenant_id' => $this->tenant_id,
            'competitor_id' => $this->competitor_id,
            'insight_id' => $this->id,
            'alert_type' => $type,
            'recipient_id' => $recipientId,
            'status' => 'pending',
        ]);
    }
}
