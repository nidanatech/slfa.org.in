<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'name',
        'icon',
        'is_global_active',
    ];

    protected $casts = [
        'is_global_active' => 'boolean',
    ];

    public function tenantSettings()
    {
        return $this->hasMany(TenantChannelSetting::class, 'channel_type_id');
    }
}
