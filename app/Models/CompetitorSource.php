<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorSource extends Model
{
    protected $fillable = [
        'competitor_id',
        'source_type',
        'platform',
        'url',
        'is_active',
        'validation_status',
        'validation_error',
        'last_scraped_at',
        'scrape_frequency',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(CompetitorSnapshot::class, 'source_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(CompetitorChange::class, 'source_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('source_type', $type);
    }

    public function scopeDueForScrape($query)
    {
        return $query->where('is_active', true)
            ->where('validation_status', 'valid')
            ->where(function($q) {
                $q->whereNull('last_scraped_at')
                    ->orWhere(function($q2) {
                        $q2->where('scrape_frequency', 'hourly')
                            ->where('last_scraped_at', '<', now()->subHour());
                    })
                    ->orWhere(function($q2) {
                        $q2->where('scrape_frequency', 'daily')
                            ->where('last_scraped_at', '<', now()->subDay());
                    })
                    ->orWhere(function($q2) {
                        $q2->where('scrape_frequency', 'weekly')
                            ->where('last_scraped_at', '<', now()->subWeek());
                    });
            });
    }

    // Helper Methods
    public function needsScraping(): bool
    {
        if (!$this->is_active || $this->validation_status !== 'valid') {
            return false;
        }

        if (!$this->last_scraped_at) {
            return true;
        }

        return match($this->scrape_frequency) {
            'hourly' => $this->last_scraped_at->lt(now()->subHour()),
            'daily' => $this->last_scraped_at->lt(now()->subDay()),
            'weekly' => $this->last_scraped_at->lt(now()->subWeek()),
            default => false,
        };
    }

    public function getLatestSnapshot(): ?CompetitorSnapshot
    {
        return $this->snapshots()->latest('captured_at')->first();
    }

    public function validateUrl(): bool
    {
        // Basic URL validation
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->update([
                'validation_status' => 'invalid',
                'validation_error' => 'Invalid URL format'
            ]);
            return false;
        }

        $this->update(['validation_status' => 'valid', 'validation_error' => null]);
        return true;
    }

    public function updateLastScraped(): void
    {
        $this->update(['last_scraped_at' => now()]);
    }
}
