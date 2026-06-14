<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarAvailability extends Model
{
    use HasFactory;

    protected $table = 'calendar_availability';

    protected $fillable = [
        'calendar_id',
        'day_of_week', // 0-6
        'start_time',
        'end_time',
    ];

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }
}
