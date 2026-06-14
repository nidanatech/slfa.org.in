<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorReview extends Model
{
    protected $fillable = [
        'vendor_id',
        'user_id',
        'rating',
        'review_text',
        'sentiment_score',
        'sentiment_label',
        'is_verified',
    ];

    protected $casts = [
        'rating' => 'float',
        'sentiment_score' => 'float',
        'is_verified' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scores()
    {
        return $this->hasMany(VendorReviewScore::class, 'review_id');
    }
}
