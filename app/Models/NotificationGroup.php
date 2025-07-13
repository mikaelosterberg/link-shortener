<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Users assigned to this notification group
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    /**
     * Active users in this notification group
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Notification channels for this group
     */
    public function channels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    /**
     * Active notification channels for this group
     */
    public function activeChannels(): HasMany
    {
        return $this->channels()->where('is_active', true);
    }

    /**
     * Link notifications using this group
     */
    public function linkNotifications(): HasMany
    {
        return $this->hasMany(LinkNotification::class);
    }

    /**
     * Get all notification targets (users + channels) for this group
     */
    public function getAllTargets(): array
    {
        $targets = [];

        // Add user emails
        foreach ($this->activeUsers as $user) {
            $targets[] = [
                'type' => 'email',
                'target' => $user->email,
                'name' => $user->name,
            ];
        }

        // Add channels
        foreach ($this->activeChannels as $channel) {
            $targets[] = [
                'type' => $channel->type,
                'target' => $channel->config,
                'name' => $channel->name,
            ];
        }

        return $targets;
    }

    /**
     * Scope to only active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
