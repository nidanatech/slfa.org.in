<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAdmission extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'adult_count', 'child_count', 'notes', 'status'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
