<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotDailyTokenUsage extends Model
{
    protected $guarded = [];
    protected $table = 'bot_daily_token_usage';

    protected $casts = [
        'date' => 'date',
        'total_cost_usd' => 'decimal:6',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
