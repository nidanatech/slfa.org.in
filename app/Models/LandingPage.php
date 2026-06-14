<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class LandingPage extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'name',
        'slug',
        'content',
        'settings',
        'is_published',
        'published_at',
        'generation_prompt',
        'brand_context',
        'language',
        'seo_metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'brand_context' => 'array',
        'seo_metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(LandingPageSubmission::class);
    }

    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id');
    }

    public function aiBot()
    {
        return $this->belongsTo(\App\Models\AiBot::class, 'ai_bot_id');
    }
}
