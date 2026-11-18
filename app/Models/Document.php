<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'file_path', 'category', 'uploaded_at'];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }
}
