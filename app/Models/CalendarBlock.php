<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarBlock extends Model
{
    use HasFactory;

    protected $table = 'calendar_blocks';

    protected $fillable = [
        'calendar_id',
        'start_datetime',
        'end_datetime',
        'reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }
}
