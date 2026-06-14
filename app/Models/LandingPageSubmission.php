<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LandingPageSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'form_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
