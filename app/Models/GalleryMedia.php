<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryMedia extends Model
{
    protected $fillable = ['album_id', 'programme_item_id', 'path', 'type', 'caption', 'sort_order'];

    public function album()
    {
        return $this->belongsTo(GalleryAlbum::class, 'album_id');
    }

    public function programmeItem()
    {
        return $this->belongsTo(EventProgrammeItem::class, 'programme_item_id');
    }
}
