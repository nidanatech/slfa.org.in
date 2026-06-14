<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarmupLog extends Model
{
    protected $fillable = [
        'email_account_id',
        'message_id',
        'recipient',
        'sent_at',
        'replied_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'replied_at' => 'datetime',
    ];
}
