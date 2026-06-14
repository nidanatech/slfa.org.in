<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantChannelSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function type()
    {
        return $this->belongsTo(ChannelType::class, 'channel_type_id');
    }
}
