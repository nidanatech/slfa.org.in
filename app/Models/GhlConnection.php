<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class GhlConnection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'location_id',
        'company_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'settings'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'settings' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
