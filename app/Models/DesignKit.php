<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Facades\Storage;

class DesignKit extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'name',
        'template_type',
        'logo_vertical',
        'logo_horizontal',
        'colors',
        'fonts',
        'is_active',
    ];

    protected $casts = [
        'colors' => 'array',
        'fonts' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getLogoVerticalUrlAttribute()
    {
        return $this->logo_vertical ? Storage::url($this->logo_vertical) : null;
    }

    public function getLogoHorizontalUrlAttribute()
    {
        return $this->logo_horizontal ? Storage::url($this->logo_horizontal) : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function deleteLogos()
    {
        if ($this->logo_vertical) {
            Storage::disk('public')->delete($this->logo_vertical);
        }
        if ($this->logo_horizontal) {
            Storage::disk('public')->delete($this->logo_horizontal);
        }
    }
}
