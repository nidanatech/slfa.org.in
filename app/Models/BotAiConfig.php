<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotAiConfig extends Model
{
    protected $guarded = [];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
