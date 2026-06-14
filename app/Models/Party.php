<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Party extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'party_type',
        'name',
        'gstin',
        'pan',
        'currency',
        'payment_terms',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'party_addresses')
                    ->withPivot('address_type', 'is_default')
                    ->withTimestamps();
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'party_contacts')
                    ->withPivot('is_primary', 'receives_invoice')
                    ->withTimestamps();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
