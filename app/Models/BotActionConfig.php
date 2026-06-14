<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotActionConfig extends Model
{
    protected $guarded = [];

    protected $casts = [
        'config_json' => 'array',
    ];

    public function action()
    {
        return $this->belongsTo(BotAction::class, 'bot_action_id');
    }
}
