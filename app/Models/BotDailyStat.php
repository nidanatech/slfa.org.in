<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotDailyStat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
