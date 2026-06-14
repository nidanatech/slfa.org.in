<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use App\Models\ChannelType;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use Billable, SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'name', 'plan_type', 'is_active', 'settings', 'openai_key', 'resource_limits', 'dashboard_assistant_id'
    ];

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->openai_key)) {
                $tenant->openai_key = config('services.openai.key');
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'resource_limits' => 'array',
    ];

    /**
     * Get the tenant's current active plan.
     */
    public function getPlanAttribute()
    {
        return $this->subscriptions()->active()->first()?->plan;
    }

    /**
     * Check if tenant is within quota for a resource.
     */
    public function canCreate(string $resource): bool
    {
        return app(\App\Services\QuotaService::class)->canCreate($this, $resource);
    }

    /**
     * Get limit for a resource.
     */
    public function getQuotaLimit(string $resource): int
    {
        return app(\App\Services\QuotaService::class)->getLimit($this, $resource);
    }

    /**
     * Get current usage for a resource.
     */
    public function getQuotaUsage(string $resource): int
    {
        return app(\App\Services\QuotaService::class)->getUsage($this, $resource);
    }

    protected $hidden = [
        'openai_key',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function bots()
    {
        return $this->hasMany(AiBot::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function channelSettings()
    {
        return $this->hasMany(TenantChannelSetting::class);
    }

    public function stripeConfig()
    {
        return $this->hasOne(TenantStripeConfig::class);
    }

    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function pipelines()
    {
        return $this->hasMany(Pipeline::class);
    }

    public function events()
    {
        return $this->hasManyThrough(Event::class, Calendar::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function isChannelEnabled(string $channelTypeId): bool
    {
        // 1. Check Global Status
        $type = ChannelType::find($channelTypeId);
        if (!$type || !$type->is_global_active) {
            return false;
        }

        // 2. Check Tenant Override
        $setting = $this->channelSettings()->where('channel_type_id', $channelTypeId)->first();
        if ($setting) {
            return (bool) $setting->is_active;
        }

        // 3. Default to Global Status
        return true;
    }
}
