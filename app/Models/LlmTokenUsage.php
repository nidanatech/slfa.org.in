<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmTokenUsage extends Model
{
    protected $guarded = [];
    protected $table = 'llm_token_usage';

    protected $casts = [
        'cost_usd' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
