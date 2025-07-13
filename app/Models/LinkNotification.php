<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_id',
        'notification_group_id',
        'notification_type_id',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * The link this notification is for
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * The notification group to notify
     */
    public function notificationGroup(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class);
    }

    /**
     * The type of notification
     */
    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    /**
     * Get the effective settings for this notification
     * (combines type defaults with link-specific overrides)
     */
    public function getEffectiveSettings(): array
    {
        $typeDefaults = $this->notificationType->default_settings ?? [];
        $linkOverrides = $this->settings ?? [];

        return array_merge($typeDefaults, $linkOverrides);
    }

    /**
     * Scope to only active notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
