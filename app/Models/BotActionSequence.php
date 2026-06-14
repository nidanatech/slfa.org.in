<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotActionSequence extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scenario()
    {
        return $this->belongsTo(BotActionScenario::class, 'scenario_id');
    }
}
