<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class KnowledgeSource extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'source_config_json' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class, 'source_id');
    }
}
