<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public function getPermissionsTeamId()
    {
        return $this->tenant_id;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'extension',
        'profile_image',
        'calendar_id',
        'online_status',
        'account_status',
        'password',
        'role',
        'tenant_id',
        'is_system_user',
        'system_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class);
    }
    
    // The calendar this user is "currently" viewing/using default or assigned
    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    // Calendars owned by this user
    public function calendars()
    {
        return $this->hasMany(Calendar::class, 'owner_id');
    }

    public function assignedConversations()
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }


}
