<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompetitorChange extends Model
{
    protected $fillable = [
        'source_id',
        'competitor_id',
        'change_type',
        'category',
        'severity',
        'title',
        'description',
        'before_snapshot_id',
        'after_snapshot_id',
        'diff_data',
        'detected_at',
        'is_read',
    ];

    protected $casts = [
        'diff_data' => 'array',
        'detected_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    // Relationships
    public function source(): BelongsTo
    {
        return $this->belongsTo(CompetitorSource::class, 'source_id');
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function beforeSnapshot(): BelongsTo
    {
        return $this->belongsTo(CompetitorSnapshot::class, 'before_snapshot_id');
    }

    public function afterSnapshot(): BelongsTo
    {
        return $this->belongsTo(CompetitorSnapshot::class, 'after_snapshot_id');
    }

    public function insight(): HasOne
    {
        return $this->hasOne(CompetitorInsight::class, 'change_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('change_type', $type);
    }

    // Helper Methods
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function generateInsight(): CompetitorInsight
    {
        // This will be called by the AI analysis job
        return CompetitorInsight::create([
            'tenant_id' => $this->competitor->tenant_id,
            'competitor_id' => $this->competitor_id,
            'change_id' => $this->id,
            'insight_type' => 'tactical',
            'priority' => $this->severity,
            'title' => "Insight for: {$this->title}",
            'summary' => 'AI analysis pending...',
            'analysis' => '',
            'generated_by' => 'ai',
            'generated_at' => now(),
        ]);
    }
}
