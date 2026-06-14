<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class GoogleCalendarConnection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'calendar_id',
        'google_email',
        'google_calendar_id',
        'google_calendar_name',
        'access_token',
        'refresh_token',
        'expires_at',
        'sync_enabled',
        'last_synced_at',
        'sync_errors',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'sync_enabled' => 'boolean',
        'sync_errors' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'google_calendar_connection_id');
    }

    // Helper methods
    public function isTokenExpired(): bool
    {
        return $this->expires_at && now()->gte($this->expires_at);
    }

    public function needsTokenRefresh(): bool
    {
        // Refresh if expired or expiring in next 5 minutes
        return $this->expires_at && now()->addMinutes(5)->gte($this->expires_at);
    }
}
