<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class KnowledgeBase extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function sources()
    {
        return $this->hasMany(KnowledgeSource::class);
    }

    public function bots()
    {
        return $this->belongsToMany(AiBot::class, 'bot_knowledge_bases', 'knowledge_base_id', 'bot_id');
    }
}
