<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotGoal extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
