<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_group_id',
        'name',
        'type',
        'config',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * The notification group this channel belongs to
     */
    public function notificationGroup(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class);
    }

    /**
     * Get the channel types available
     */
    public static function getAvailableTypes(): array
    {
        return [
            'email' => 'Email',
            'webhook' => 'Webhook',
            'slack' => 'Slack',
            'discord' => 'Discord',
            'teams' => 'Microsoft Teams',
        ];
    }

    /**
     * Get the config schema for each channel type
     */
    public static function getConfigSchema(string $type): array
    {
        return match ($type) {
            'email' => [
                'email' => 'Email Address',
                'name' => 'Display Name (optional)',
            ],
            'webhook' => [
                'url' => 'Webhook URL',
                'method' => 'HTTP Method (GET/POST) (optional)',
                'headers' => 'Custom Headers (JSON) (optional)',
            ],
            'slack' => [
                'webhook_url' => 'Slack Webhook URL',
                'channel' => 'Channel Name (optional)',
                'username' => 'Bot Username (optional)',
            ],
            'discord' => [
                'webhook_url' => 'Discord Webhook URL',
                'username' => 'Bot Username (optional)',
            ],
            'teams' => [
                'webhook_url' => 'Teams Webhook URL',
            ],
            default => [],
        };
    }

    /**
     * Validate the configuration for this channel type
     */
    public function validateConfig(): bool
    {
        $schema = self::getConfigSchema($this->type);
        $config = $this->config ?? [];

        foreach ($schema as $key => $label) {
            if (str_contains($label, '(optional)')) {
                continue;
            }

            if (empty($config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a formatted display of the channel configuration
     */
    public function getConfigDisplayAttribute(): string
    {
        $config = $this->config ?? [];

        return match ($this->type) {
            'email' => $config['email'] ?? 'No email configured',
            'webhook' => $config['url'] ?? 'No URL configured',
            'slack' => $config['channel'] ?? $config['webhook_url'] ?? 'No configuration',
            'discord' => $config['username'] ?? 'Discord Webhook',
            'teams' => 'Teams Webhook',
            default => 'Unknown configuration',
        };
    }

    /**
     * Scope to only active channels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
