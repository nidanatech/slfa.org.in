<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardConversation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(DashboardMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
