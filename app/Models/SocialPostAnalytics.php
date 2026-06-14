<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class SocialPostAnalytics extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'social_post_channel_id',
        'likes',
        'comments',
        'shares',
        'clicks',
        'impressions',
        'reach',
        'engagement_rate',
        'platform_insights',
        'last_synced_at',
    ];

    protected $casts = [
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'clicks' => 'integer',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagement_rate' => 'decimal:2',
        'platform_insights' => 'array',
        'last_synced_at' => 'datetime',
    ];

    // Relationships
    public function postChannel(): BelongsTo
    {
        return $this->belongsTo(SocialPostChannel::class, 'social_post_channel_id');
    }

    // Helper Methods
    public function getTotalEngagement(): int
    {
        return $this->likes + $this->comments + $this->shares;
    }

    public function calculateEngagementRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->getTotalEngagement() / $this->impressions) * 100, 2);
    }

    public function updateEngagementRate(): void
    {
        $this->update([
            'engagement_rate' => $this->calculateEngagementRate(),
        ]);
    }

    public function needsSync(): bool
    {
        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->diffInHours(now()) >= 6;
    }
}
