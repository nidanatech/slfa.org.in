<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    protected $appends = ['public_slug'];

    public function getPublicSlugAttribute(): string
    {
        return (\Illuminate\Support\Str::slug($this->title) ?: 'news').'--'.$this->public_id;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'public_id' && preg_match('/--([0-9A-HJKMNP-TV-Z]{26})$/i', $value, $matches)) {
            $value = $matches[1];
        }

        return parent::resolveRouteBinding($value, $field);
    }
    protected $fillable = [
        'title', 'body', 'category',
        'cover_image_path', 'published_at', 'author_id',
        'public_id', 'meta_title', 'meta_description', 'og_image_path',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $post) {
            $post->public_id ??= (string) \Illuminate\Support\Str::ulid();
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function photos()
    {
        return $this->hasMany(NewsPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }
}
