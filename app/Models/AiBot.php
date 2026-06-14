<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class AiBot extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\ManagesOpenAiAssistant;
    use \App\Traits\HandlesBotConversations;
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'config' => 'array',
        'supported_channels' => 'array',
        'sleep_on_manual_message' => 'boolean',
        'confidence_threshold' => 'float',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($bot) {
            if ($bot->is_primary) {
                // Unset is_primary for all other bots in this tenant
                static::where('tenant_id', $bot->tenant_id)
                    ->where('id', '!=', $bot->id)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function aiConfig()
    {
        return $this->hasOne(BotAiConfig::class, 'bot_id');
    }

    public function goals()
    {
        return $this->hasMany(BotGoal::class, 'bot_id');
    }

    public function actions()
    {
        return $this->hasMany(BotAction::class, 'bot_id');
    }

    public function sessions()
    {
        return $this->hasMany(BotSession::class, 'bot_id');
    }

    public function knowledgeBases()
    {
        return $this->belongsToMany(KnowledgeBase::class, 'bot_knowledge_bases', 'bot_id', 'knowledge_base_id')
                    ->withPivot(['priority', 'is_active'])
                    ->withTimestamps();
    }

    public function tokenUsages()
    {
        return $this->hasMany(LlmTokenUsage::class, 'bot_id');
    }

    public function dailyTokenUsages()
    {
        return $this->hasMany(BotDailyTokenUsage::class, 'bot_id');
    }
}
