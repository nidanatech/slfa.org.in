<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $table = 'calendar_events';

    protected $fillable = [
        'calendar_id',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'timezone',
        'status',
        'mode',
        'location',
        'meeting_link',
        'contact_id',
        'product_id',
        'product_variation_id',
        'opportunity_id',
        'conversation_id',
        'owner_id',
        'created_by',
        'source',
        'payment_status',
        'stripe_session_id',
        'google_event_id',
        'google_calendar_connection_id',
        'last_synced_at',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function googleConnection()
    {
        return $this->belongsTo(\App\Models\GoogleCalendarConnection::class, 'google_calendar_connection_id');
    }

    // Helper methods
    public function shouldSyncToGoogle(): bool
    {
        return $this->calendar?->googleConnection?->sync_enabled ?? false;
    }
}
