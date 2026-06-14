<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Calendar extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarFactory> */
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'calendar_type',
        'owner_id',
        'tenant_id',
        'timezone',
        'color',
        'is_default',
        'visibility',
        'slug',
        'slot_duration',
        'buffer_time',
        'is_public',
        'product_id',
        'product_variation_id'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function availability()
    {
        return $this->hasMany(CalendarAvailability::class);
    }

    public function blocks()
    {
        return $this->hasMany(CalendarBlock::class);
    }

    public function googleConnection()
    {
        return $this->hasOne(\App\Models\GoogleCalendarConnection::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    // Helper methods
    public function isGoogleSynced(): bool
    {
        return $this->googleConnection && $this->googleConnection->sync_enabled;
    }
}
