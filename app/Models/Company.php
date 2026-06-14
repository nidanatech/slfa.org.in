<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Company extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'website', 'logo',
        'address', 'city', 'state', 'country', 'zip', 'description'
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
