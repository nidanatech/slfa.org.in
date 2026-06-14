<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ConversationReferral extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'conversation_id',
        'tenant_id',
        'source',
        'type',
        'ref',
        'ad_id',
        'ad_title',
        'post_id',
        'photo_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = ['description', 'is_from_ad'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get a human-readable description of the referral source
     */
    public function getDescriptionAttribute(): string
    {
        return match($this->source) {
            'ADS' => "Clicked on ad" . ($this->ad_title ? ": {$this->ad_title}" : ''),
            'SHORTLINK' => "Clicked m.me link" . ($this->ref ? " ({$this->ref})" : ''),
            'CUSTOMER_CHAT_PLUGIN' => "Started chat from website plugin",
            'MESSENGER_CODE' => "Scanned Messenger code",
            'DISCOVER_TAB' => "Found via Messenger Discover",
            'FACEBOOK_LOGIN' => "Connected via Facebook Login",
            default => $this->source ? "Started conversation via {$this->source}" : "Direct message"
        };
    }

    /**
     * Check if this is from a paid ad
     */
    public function getIsFromAdAttribute(): bool
    {
        return $this->source === 'ADS';
    }
}
