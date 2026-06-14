<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotActionScenario extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'create_task' => 'boolean',
        'trigger_config_json' => 'array',
    ];

    public function action()
    {
        return $this->belongsTo(BotAction::class, 'bot_action_id');
    }

    public function sequences()
    {
        return $this->hasMany(BotActionSequence::class, 'scenario_id')->orderBy('sequence_order');
    }
}
