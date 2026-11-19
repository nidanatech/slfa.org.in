<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSponsor extends Model
{
    protected $fillable = ['name', 'website_url', 'logo_path', 'tier', 'sort_order'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
