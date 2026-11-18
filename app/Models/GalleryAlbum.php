<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryAlbum extends Model
{
    protected $fillable = ['title', 'event_id', 'cover_image_path'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function media()
    {
        return $this->hasMany(GalleryMedia::class, 'album_id')->orderBy('sort_order');
    }
}
