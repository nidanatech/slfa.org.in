<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title', 'description', 'event_type',
        'location', 'start_at', 'end_at',
        'cover_image_path', 'rsvp_enabled',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'rsvp_enabled' => 'boolean',
        ];
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function albums()
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())->orderBy('start_at');
    }

    public function scopePast($query)
    {
        return $query->where('start_at', '<', now())->orderByDesc('start_at');
    }
}
