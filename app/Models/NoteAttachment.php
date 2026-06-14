<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteAttachment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
