<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class VendorRatingCriteria extends Model
{
    use BelongsToTenant;

    protected $table = 'vendor_rating_criteria';

    protected $fillable = [
        'name',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];
}
