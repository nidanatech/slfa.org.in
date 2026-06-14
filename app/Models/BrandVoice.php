<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class BrandVoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'brand_name',
        'business_type',
        'company_website',
        'company_email',
        'company_address',
        'phone_number',
        'business_hours',
        'target_audience',
        'customer_pain_points',
        'brand_promise',
        'brand_values',
        'brand_description',
        'competitive_advantage',
        'unique_selling_proposition',
        'risks_of_inaction',
        'call_to_action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
