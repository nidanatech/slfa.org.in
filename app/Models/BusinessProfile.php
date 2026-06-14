<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class BusinessProfile extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'authorized_representative' => 'array',
    ];
    
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($profile) {
            if ($profile->isDirty('friendly_name') || empty($profile->slug)) {
                $profile->slug = static::generateUniqueSlug($profile->friendly_name ?: 'business', $profile->id);
            }
        });
    }

    protected static function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
