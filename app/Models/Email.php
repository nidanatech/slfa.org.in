<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Email extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'email_account_id',
        'message_id',
        'thread_id',
        'subject',
        'from_address',
        'from_name',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'reply_to',
        'body_text',
        'body_html',
        'snippet',
        'direction',
        'is_read',
        'is_converted',
        'is_starred',
        'is_draft',
        'is_warmup',
        'labels',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'bcc_addresses' => 'array',
        'labels' => 'array',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_draft' => 'boolean',
        'is_warmup' => 'boolean',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    // Relationships
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    // Scopes
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('received_at', 'desc');
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function toggleStar()
    {
        $this->update(['is_starred' => !$this->is_starred]);
    }

    public function hasAttachments()
    {
        return $this->attachments()->exists();
    }
}
