<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasPublicId;
use App\Traits\BelongsToTenant;

class Channel extends Model
{
    use HasPublicId, SoftDeletes, BelongsToTenant;

    protected $guarded = [];

    protected $appends = ['public_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Scopes
    public function scopeForms($query)
    {
        return $query->where('type', 'lead_form');
    }

    public function scopeWordpress($query)
    {
        return $query->where('type', 'wordpress');
    }
}
