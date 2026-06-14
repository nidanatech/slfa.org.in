<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorReviewScore extends Model
{
    protected $fillable = [
        'review_id',
        'criteria_id',
        'score',
    ];

    public function review()
    {
        return $this->belongsTo(VendorReview::class, 'review_id');
    }

    public function criteria()
    {
        return $this->belongsTo(VendorRatingCriteria::class, 'criteria_id');
    }
}
