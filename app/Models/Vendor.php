<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Vendor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'website',
        'category',
        'risk_level',
        'overall_score',
        'status',
        'notes',
        'social_handles',
        'business_profiles',
        'social_risk_score',
        'registration_type',
        'tax_id',
        'bank_details',
        'region',
        'onboarding_status',
    ];

    protected $casts = [
        'social_handles' => 'array',
        'business_profiles' => 'array',
        'bank_details' => 'array',
        'risk_level' => 'string',
        'overall_score' => 'float',
        'social_risk_score' => 'float',
    ];

    public function reviews()
    {
        return $this->hasMany(VendorReview::class);
    }

    public function socialPosts()
    {
        return $this->hasMany(VendorSocialPost::class);
    }

    public function documents()
    {
        return $this->hasMany(VendorDocument::class);
    }
}
