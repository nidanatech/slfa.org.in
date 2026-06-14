<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDailyTokenUsage extends Model
{
    protected $guarded = [];
    protected $table = 'tenant_daily_token_usage';

    protected $casts = [
        'date' => 'date',
        'total_cost_usd' => 'decimal:6',
    ];
}
