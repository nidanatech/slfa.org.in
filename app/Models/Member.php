<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'full_name', 'email', 'phone',
        'membership_type', 'sri_lanka_connection',
        'occupation', 'company_name', 'designation', 'city',
        'photo_path', 'status', 'directory_visible',
        'directory_consent_at', 'show_contact_info', 'featured',
        'member_id_string', 'date_of_birth', 'joined_at', 'renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'directory_visible' => 'boolean',
            'show_contact_info' => 'boolean',
            'featured' => 'boolean',
            'directory_consent_at' => 'datetime',
            'joined_at' => 'datetime',
            'renewed_at' => 'datetime',
            'date_of_birth' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate a unique member ID string like SLFA-2026-001
     */
    public static function generateMemberId(): string
    {
        $year = now()->year;
        $lastMember = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastMember ? ((int) substr($lastMember->member_id_string, -3)) + 1 : 1;

        return sprintf('SLFA-%d-%03d', $year, $sequence);
    }
}
