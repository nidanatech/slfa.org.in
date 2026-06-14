<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderIdentity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dkim_attributes' => 'array',
        'is_default' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
