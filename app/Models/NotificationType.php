<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'default_groups',
        'notify_link_owner',
        'apply_to_link_groups',
        'exclude_blocked_links',
        'is_active',
        'default_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_link_owner' => 'boolean',
        'apply_to_link_groups' => 'array',
        'exclude_blocked_links' => 'boolean',
        'default_groups' => 'array',
        'default_settings' => 'array',
    ];

    /**
     * Get the default notification groups for this type
     */
    public function getDefaultGroups()
    {
        if (empty($this->default_groups)) {
            return collect();
        }

        return NotificationGroup::whereIn('id', $this->default_groups)->get();
    }

    /**
     * Link notifications of this type
     */
    public function linkNotifications(): HasMany
    {
        return $this->hasMany(LinkNotification::class);
    }

    /**
     * Get the default notification types
     */
    public static function getDefaultTypes(): array
    {
        return [
            [
                'name' => 'link_health',
                'display_name' => 'Link Health Alert',
                'description' => 'Notifications when links go down or come back online',
                'default_settings' => [
                    'retry_attempts' => 3,
                    'retry_delay' => 300, // 5 minutes
                    'escalation_delay' => [3600, 14400, 43200], // 1h, 4h, 12h
                    'recovery_notification' => true,
                ],
            ],
            [
                'name' => 'system_alert',
                'display_name' => 'System Alert',
                'description' => 'Critical system notifications and maintenance alerts',
                'default_settings' => [
                    'priority' => 'high',
                    'immediate_delivery' => true,
                ],
            ],
            [
                'name' => 'maintenance',
                'display_name' => 'Maintenance Notification',
                'description' => 'Scheduled maintenance and update notifications',
                'default_settings' => [
                    'priority' => 'medium',
                    'advance_notice' => 86400, // 24 hours
                ],
            ],
        ];
    }

    /**
     * Scope to only active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
