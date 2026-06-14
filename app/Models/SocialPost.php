<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Traits\BelongsToTenant;

class SocialPost extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'user_id',
        'brand_voice_id',
        'design_kit_id',
        'content',
        'hashtags',
        'mentions',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'scheduled_for',
        'published_at',
        'metadata',
        'ai_generated',
        'ai_prompt',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'mentions' => 'array',
        'metadata' => 'array',
        'ai_generated' => 'boolean',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brandVoice(): BelongsTo
    {
        return $this->belongsTo(BrandVoice::class);
    }

    public function designKit(): BelongsTo
    {
        return $this->belongsTo(DesignKit::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'social_post_channels')
            ->withPivot('status', 'platform_post_id', 'platform_url', 'published_at', 'error_message')
            ->withTimestamps();
    }

    public function postChannels(): HasMany
    {
        return $this->hasMany(SocialPostChannel::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SocialPostMedia::class)->orderBy('sort_order');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SocialPostApproval::class)->orderBy('created_at', 'desc');
    }

    public function analytics(): HasManyThrough
    {
        return $this->hasManyThrough(
            SocialPostAnalytics::class,
            SocialPostChannel::class,
            'social_post_id',
            'social_post_channel_id'
        );
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_for', '<=', now());
    }

    // Helper Methods
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function canBePublished(): bool
    {
        return in_array($this->status, ['draft', 'approved', 'scheduled']) 
            && ($this->approval_status === 'approved' || $this->approval_status === null);
    }

    public function getTotalEngagement(): int
    {
        return $this->analytics->sum(function ($analytic) {
            return $analytic->likes + $analytic->comments + $analytic->shares;
        });
    }
}
