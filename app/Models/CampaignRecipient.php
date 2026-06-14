<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'email',
        'name',
        'custom_fields',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'bounce_reason',
        'error_message',
        'opens_count',
        'clicks_count',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignClick::class, 'recipient_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeOpened($query)
    {
        return $query->where('status', 'opened');
    }

    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    // Helper Methods
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsOpened()
    {
        if (!$this->opened_at) {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }
        $this->increment('opens_count');
    }

    public function markAsClicked()
    {
        $this->update(['status' => 'clicked', 'clicked_at' => now()]);
        $this->increment('clicks_count');
    }

    public function markAsBounced($reason = null)
    {
        $this->update([
            'status' => 'bounced',
            'bounced_at' => now(),
            'bounce_reason' => $reason,
        ]);
    }

    public function markAsFailed($error = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
