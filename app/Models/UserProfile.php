<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'connection_with_srilanka' => 'array',
        'date_of_birth' => 'date',
        'date_of_incorporation' => 'date',
        'date_of_application' => 'date',
        'entrance_fee_received' => 'date',
        'membership_fee_received' => 'date',
        'payment_date' => 'date',
        'date_of_enrollment' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
