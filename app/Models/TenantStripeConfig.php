<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantStripeConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'stripe_public_key',
        'stripe_secret_key',
        'webhook_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
