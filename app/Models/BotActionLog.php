<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotActionLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }

    public function action()
    {
        return $this->belongsTo(BotAction::class, 'bot_action_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
