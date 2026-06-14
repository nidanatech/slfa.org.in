<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorSnapshot extends Model
{
    protected $fillable = [
        'source_id',
        'snapshot_type',
        'content_hash',
        'raw_content',
        'parsed_data',
        'metadata',
        'file_path',
        'captured_at',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'metadata' => 'array',
        'captured_at' => 'datetime',
    ];

    // Relationships
    public function source(): BelongsTo
    {
        return $this->belongsTo(CompetitorSource::class, 'source_id');
    }

    public function changesAsBefore(): HasMany
    {
        return $this->hasMany(CompetitorChange::class, 'before_snapshot_id');
    }

    public function changesAsAfter(): HasMany
    {
        return $this->hasMany(CompetitorChange::class, 'after_snapshot_id');
    }

    // Helper Methods
    public function compareWith(CompetitorSnapshot $other): array
    {
        // Simple hash comparison
        if ($this->content_hash === $other->content_hash) {
            return ['changed' => false];
        }

        return [
            'changed' => true,
            'old_hash' => $this->content_hash,
            'new_hash' => $other->content_hash,
        ];
    }

    public function extractStructuredData(): array
    {
        return $this->parsed_data ?? [];
    }
}
