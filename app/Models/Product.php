<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'currency',
        'stripe_product_id',
        'stripe_price_id',
        'has_variations',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'has_variations' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
