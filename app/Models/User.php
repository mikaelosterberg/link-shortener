<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
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

    public function canAccessPanel(Panel $panel): bool
    {
        // Only users with the panel_user role or super_admin can access
        return $this->hasRole(['super_admin', 'panel_user', 'admin', 'user']);
    }

    /**
     * Convert a UTC datetime to the user's timezone
     */
    public function convertToUserTimezone($datetime)
    {
        if (!$datetime) {
            return null;
        }

        return $datetime->setTimezone(new \DateTimeZone($this->timezone ?? 'UTC'));
    }

    /**
     * Get the user's timezone object
     */
    public function getTimezoneObject()
    {
        return new \DateTimeZone($this->timezone ?? 'UTC');
    }
}
