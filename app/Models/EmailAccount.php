<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use App\Traits\BelongsToTenant;

class EmailAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'provider',
        'email_address',
        'display_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_account_id',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'sync_enabled',
        'last_synced_at',
        'sync_from_date',
        'status',
        'error_message',
        'warmup_enabled',
        'warmup_started_at',
        'warmup_completed_at',
        'last_warmup_at',
        'warmup_day',
        'warmup_emails_sent',
        'warmup_skipped',
        'warmup_skipped_at',
        'bounce_rate',
        'spam_reports',
        'provider_error_rate',
        'inbox_rate',
        'reply_rate',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
        'sync_from_date' => 'datetime',
        'token_expires_at' => 'datetime',
        'warmup_enabled' => 'boolean',
        'warmup_started_at' => 'datetime',
        'warmup_completed_at' => 'datetime',
        'last_warmup_at' => 'datetime',
        'warmup_skipped' => 'boolean',
        'warmup_skipped_at' => 'datetime',
        'imap_password' => 'encrypted',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'imap_password', // Hide from array/JSON
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(EmailSyncJob::class);
    }

    // Accessors & Mutators for encrypted tokens
    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }

    // Helper methods
    public function isTokenExpired()
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function needsSync()
    {
        if (!$this->sync_enabled || $this->status !== 'active') {
            return false;
        }

        // Sync if never synced or last sync was more than 5 minutes ago
        return !$this->last_synced_at || $this->last_synced_at->lt(now()->subMinutes(5));
    }

    public function markAsSynced()
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function markAsError($message)
    {
        $this->update([
            'status' => 'error',
            'error_message' => $message,
        ]);
    }

    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'error_message' => null,
        ]);
    }
}
