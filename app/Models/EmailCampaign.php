<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class EmailCampaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'email_account_id',
        'name',
        'campaign_type',
        'subject',
        'preheader',
        'from_name',
        'reply_to',
        'content_html',
        'content_text',
        'template_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignClick::class, 'campaign_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    // Helper Methods
    public function getOpenRateAttribute()
    {
        if ($this->sent_count == 0) return 0;
        return round(($this->opened_count / $this->sent_count) * 100, 2);
    }

    public function getClickRateAttribute()
    {
        if ($this->sent_count == 0) return 0;
        return round(($this->clicked_count / $this->sent_count) * 100, 2);
    }

    public function getBounceRateAttribute()
    {
        if ($this->sent_count == 0) return 0;
        return round(($this->bounced_count / $this->sent_count) * 100, 2);
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isSending()
    {
        return $this->status === 'sending';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function markAsSending()
    {
        $this->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function incrementSentCount()
    {
        $this->increment('sent_count');
    }

    public function incrementDeliveredCount()
    {
        $this->increment('delivered_count');
    }

    public function incrementOpenedCount()
    {
        $this->increment('opened_count');
    }

    public function incrementClickedCount()
    {
        $this->increment('clicked_count');
    }

    public function incrementBouncedCount()
    {
        $this->increment('bounced_count');
    }
}
