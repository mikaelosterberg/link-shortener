<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        // Only users with admin roles can access the panel
        return $this->hasRole(['super_admin', 'admin', 'user']);
    }

    /**
     * Convert a UTC datetime to the user's timezone
     */
    public function convertToUserTimezone($datetime)
    {
        if (! $datetime) {
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

    /**
     * Notification groups this user belongs to
     */
    public function notificationGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class)
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    /**
     * Active notification groups for this user
     */
    public function activeNotificationGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->notificationGroups()->wherePivot('is_active', true);
    }
}
