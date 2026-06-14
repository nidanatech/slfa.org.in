<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BotSession extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'is_test' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
