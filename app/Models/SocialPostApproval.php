<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class SocialPostApproval extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'social_post_id',
        'user_id',
        'action',
        'comment',
        'previous_status',
        'new_status',
    ];

    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('action', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('action', 'rejected');
    }

    public function scopeForPost($query, $postId)
    {
        return $query->where('social_post_id', $postId);
    }

    // Helper Methods
    public function isApproval(): bool
    {
        return $this->action === 'approved';
    }

    public function isRejection(): bool
    {
        return $this->action === 'rejected';
    }

    public function isChangeRequest(): bool
    {
        return $this->action === 'changes_requested';
    }
}
