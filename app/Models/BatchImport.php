<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchImport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'error_log' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
