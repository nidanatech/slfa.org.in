<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsPhoto extends Model
{
    protected $fillable = ['path', 'caption', 'sort_order'];

    public function post()
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }
}
