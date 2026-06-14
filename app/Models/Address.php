<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'line1',
        'line2',
        'city',
        'state',
        'country',
        'postal_code',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parties()
    {
        return $this->belongsToMany(Party::class, 'party_addresses')
                    ->withPivot('address_type', 'is_default')
                    ->withTimestamps();
    }
}
