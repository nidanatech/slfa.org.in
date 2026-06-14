<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Contact extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'channel_id', 'company_id', 'external_id', 'name', 'avatar_url', 'meta',
        'salutation', 'first_name', 'last_name', 'gender', 'date_of_birth', 'marital_status',
        'emails', 'phones', 'contact_type', 'contact_types', 'time_zone',
        'dnd_email', 'dnd_text', 'dnd_calls', 'dnd_inbound_calls_sms', 'contact_image', 'rating',
        'pipeline_id', 'stage_id', 'stage_confidence', 'stage_updated_by', 'stage_updated_at', 'next_stage_change_allowed_at',
        'task_automation_enabled'
    ];

    protected $appends = ['primary_email', 'primary_phone'];

    protected $casts = [
        'meta' => 'array',
        'emails' => 'array',
        'phones' => 'array',
        'contact_types' => 'array',
        'date_of_birth' => 'date',
        'dnd_email' => 'boolean',
        'dnd_text' => 'boolean',
        'dnd_calls' => 'boolean',
        'dnd_inbound_calls_sms' => 'boolean',
        'stage_confidence' => 'float',
        'stage_updated_at' => 'datetime',
        'next_stage_change_allowed_at' => 'datetime',
        'created_at' => 'datetime',
        'task_automation_enabled' => 'boolean',
    ];

    public function getPrimaryEmailAttribute()
    {
        if (!empty($this->emails) && is_array($this->emails) && isset($this->emails[0])) {
            if (is_array($this->emails[0]) && isset($this->emails[0]['value'])) {
                return $this->emails[0]['value'];
            }
            if (is_string($this->emails[0])) {
                return $this->emails[0];
            }
        }
        return null;
    }

    public function getPrimaryPhoneAttribute()
    {
        if (!empty($this->phones) && is_array($this->phones) && isset($this->phones[0])) {
            if (is_array($this->phones[0]) && isset($this->phones[0]['value'])) {
                return $this->phones[0]['value'];
            }
            if (is_string($this->phones[0])) {
                return $this->phones[0];
            }
        }
        return null;
    }

    public function parties()
    {
        return $this->belongsToMany(Party::class, 'party_contacts')
                    ->withPivot('is_primary', 'receives_invoice')
                    ->withTimestamps();
    }

    public static function splitName(string $name): array
    {
        $name = trim($name);
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($contact) {
            // Automatically sync name from first and last name if provided
            if (($contact->first_name || $contact->last_name) && !$contact->name) {
                $parts = array_filter([$contact->first_name, $contact->last_name]);
                $contact->name = implode(' ', $parts);
            }
        });
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'object', 'taggables', 'object_id', 'tag_id');
    }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, 'record_id');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'object');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'object');
    }
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    // Accounting relationships
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Check if DND is active for a specific channel type.
     */
    public function isDndForChannel(?string $channelType): bool
    {
        if (!$channelType) return false;

        return match ($channelType) {
            'email', 'gmail', 'outlook', 'ses' => $this->dnd_email,
            'messenger', 'instagram', 'instagram_message', 'threads', 'whatsapp', 'google_business' => $this->dnd_text,
            default => false,
        };
    }

    // Helper methods for contact types
    public function isVendor(): bool
    {
        return in_array('vendor', $this->contact_types ?? []);
    }

    public function isStaff(): bool
    {
        return in_array('staff', $this->contact_types ?? []);
    }

    public function isCustomer(): bool
    {
        return in_array('customer', $this->contact_types ?? []);
    }

    public function isLead(): bool
    {
        return in_array('lead', $this->contact_types ?? []);
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function stageLogs()
    {
        return $this->hasMany(StageChangeLog::class);
    }

    /**
     * Ensure a contact exists for a given channel and external ID, handling race conditions.
     */
    public static function ensureContact(Channel $channel, string $externalId, array $data = []): self
    {
        return \DB::transaction(function () use ($channel, $externalId, $data) {
            $contact = self::where('channel_id', $channel->id)
                ->where('external_id', $externalId)
                ->lockForUpdate()
                ->first();

            if ($contact) {
                return $contact;
            }

            // Prepare default data
            $displayName = $data['name'] ?? 'Instagram User';
            $nameParts = self::splitName($displayName);

            $createData = array_merge([
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
                'external_id' => $externalId,
                'name' => $displayName,
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'metadata' => [
                    'platform' => $channel->type,
                    'external_id' => $externalId
                ]
            ], $data);

            return self::create($createData);
        });
    }
}
