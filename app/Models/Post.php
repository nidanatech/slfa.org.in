<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_SCHEDULED = 'scheduled';

    const TYPE_BLOG = 'blog';
    const TYPE_RESOURCE = 'resource';
    const TYPE_DOCUMENTATION = 'documentation';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
