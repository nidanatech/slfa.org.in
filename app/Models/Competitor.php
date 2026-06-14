<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Competitor extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'name',
        'website_url',
        'logo_url',
        'industry',
        'description',
        'is_active',
        'monitoring_frequency',
        'last_monitored_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_monitored_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CompetitorSource::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(CompetitorChange::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(CompetitorInsight::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CompetitorAlert::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForMonitoring($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('last_monitored_at')
                    ->orWhere(function($q2) {
                        $q2->where('monitoring_frequency', 'hourly')
                            ->where('last_monitored_at', '<', now()->subHour());
                    })
                    ->orWhere(function($q2) {
                        $q2->where('monitoring_frequency', 'daily')
                            ->where('last_monitored_at', '<', now()->subDay());
                    })
                    ->orWhere(function($q2) {
                        $q2->where('monitoring_frequency', 'weekly')
                            ->where('last_monitored_at', '<', now()->subWeek());
                    });
            });
    }

    // Helper Methods
    public function needsMonitoring(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_monitored_at) {
            return true;
        }

        return match($this->monitoring_frequency) {
            'hourly' => $this->last_monitored_at->lt(now()->subHour()),
            'daily' => $this->last_monitored_at->lt(now()->subDay()),
            'weekly' => $this->last_monitored_at->lt(now()->subWeek()),
            default => false,
        };
    }

    public function getActiveSources()
    {
        return $this->sources()->where('is_active', true)->get();
    }

    public function getRecentChanges($days = 7)
    {
        return $this->changes()
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    public function getUnreadChangesCount(): int
    {
        return $this->changes()->where('is_read', false)->count();
    }

    public function updateLastMonitored(): void
    {
        $this->update(['last_monitored_at' => now()]);
    }
}
