<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMessage extends Model
{
    protected $guarded = [];

    public function conversation()
    {
        return $this->belongsTo(DashboardConversation::class, 'dashboard_conversation_id');
    }
}
