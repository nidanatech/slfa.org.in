<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BotTrainingData extends Model
{
    use BelongsToTenant;

    protected $table = 'bot_training_data';

    protected $fillable = [
        'bot_id',
        'question',
        'answer',
        'embedding'
    ];

    protected $casts = [
        'embedding' => 'array'
    ];

    public function bot()
    {
        return $this->belongsTo(AiBot::class, 'bot_id');
    }
}
