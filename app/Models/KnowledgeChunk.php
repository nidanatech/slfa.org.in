<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $guarded = [];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }
}
