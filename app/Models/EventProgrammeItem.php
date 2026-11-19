<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventProgrammeItem extends Model
{
    protected $fillable = ['time', 'title', 'details', 'sort_order'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function media()
    {
        return $this->hasMany(GalleryMedia::class, 'programme_item_id')->orderBy('sort_order')->orderBy('id');
    }
}
