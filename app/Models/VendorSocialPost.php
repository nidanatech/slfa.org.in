<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'platform',
        'content',
        'sentiment_score',
        'embedding',
        'risk_flag',
    ];

    protected $casts = [
        'sentiment_score' => 'float',
        'risk_flag' => 'boolean',
        'embedding' => 'array', // Assuming stored as JSON in text column, or use custom caster if needed
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
