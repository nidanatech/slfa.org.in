<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppGroupParticipant extends Model
{
    protected $table = 'whatsapp_group_participants';

    protected $fillable = [
        'whatsapp_group_id',
        'contact_id',
        'role',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
