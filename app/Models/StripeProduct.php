<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class StripeProduct extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'stripe_product_id',
        'stripe_price_id',
        'name',
        'description',
        'price',
        'currency',
        'price_type',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'price' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
