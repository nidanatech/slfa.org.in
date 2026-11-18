<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeMember extends Model
{
    protected $fillable = [
        'name', 'role', 'company',
        'designation', 'photo_path', 'type', 'show_in_website', 'display_order',
    ];

    protected $casts = [
        'show_in_website' => 'boolean',
    ];
}
