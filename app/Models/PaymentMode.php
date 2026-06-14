<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PaymentMode extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'show_on_pdf',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_pdf' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
