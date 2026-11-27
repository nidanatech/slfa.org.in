<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $appends = ['public_slug'];

    public function getPublicSlugAttribute(): string
    {
        return (\Illuminate\Support\Str::slug($this->title) ?: 'event').'--'.$this->public_id;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'public_id' && preg_match('/--([0-9A-HJKMNP-TV-Z]{26})$/i', $value, $matches)) {
            $value = $matches[1];
        }

        return parent::resolveRouteBinding($value, $field);
    }
    protected $fillable = [
        'title', 'description', 'event_type',
        'location', 'start_at', 'end_at',
        'cover_image_path', 'programme_image_path', 'rsvp_enabled',
        'venue_name', 'venue_address', 'timezone', 'dress_code',
        'adult_price', 'child_price', 'child_age_range', 'contact_details',
        'public_id', 'admission_enabled', 'admission_closes_at',
        'meta_title', 'meta_description', 'og_image_path',
        'promo_video_path', 'promo_poster_path', 'promo_transcript',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'rsvp_enabled' => 'boolean',
            'admission_enabled' => 'boolean',
            'admission_closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->public_id ??= (string) \Illuminate\Support\Str::ulid();
        });
    }

    public function admissions()
    {
        return $this->hasMany(EventAdmission::class);
    }

    public function admissionsOpen(): bool
    {
        return $this->admission_enabled && $this->start_at?->isFuture()
            && (! $this->admission_closes_at || $this->admission_closes_at->isFuture());
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function albums()
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    public function programmeItems()
    {
        return $this->hasMany(EventProgrammeItem::class)->orderBy('sort_order')->orderBy('time');
    }

    public function sponsors()
    {
        return $this->hasMany(EventSponsor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())->orderBy('start_at');
    }

    public function scopePast($query)
    {
        return $query->where('start_at', '<', now())->orderByDesc('start_at');
    }
}
