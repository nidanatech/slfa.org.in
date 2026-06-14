<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotAction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'config_json' => 'array',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }

    public function scenarios()
    {
        return $this->hasMany(BotActionScenario::class, 'bot_action_id');
    }
    public function actionConfig()
    {
        return $this->hasOne(BotActionConfig::class, 'bot_action_id');
    }
}
