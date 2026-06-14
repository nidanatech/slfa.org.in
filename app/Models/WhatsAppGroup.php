<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppGroup extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'external_id',
        'name',
        'description',
        'avatar_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(WhatsAppGroupParticipant::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
