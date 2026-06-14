<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class EmailUnsubscribe extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'email',
        'campaign_id',
        'reason',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    // Helper Methods
    public static function isUnsubscribed($tenantId, $email)
    {
        return self::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->exists();
    }
}
