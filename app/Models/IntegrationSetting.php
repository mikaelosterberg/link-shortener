<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'provider',
        'key',
        'value',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get a setting value for a provider and key
     */
    public static function get(string $provider, string $key, mixed $default = null): mixed
    {
        $setting = static::where('provider', $provider)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set a setting value for a provider and key
     */
    public static function set(string $provider, string $key, mixed $value, bool $isActive = true, array $metadata = []): void
    {
        static::updateOrCreate(
            ['provider' => $provider, 'key' => $key],
            [
                'value' => $value,
                'is_active' => $isActive,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Check if a provider integration is active
     */
    public static function isActive(string $provider): bool
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all settings for a provider as key-value pairs
     */
    public static function getProviderSettings(string $provider): array
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
