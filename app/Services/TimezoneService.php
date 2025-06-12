<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TimezoneService
{
    /**
     * Format a datetime for the current user's timezone
     */
    public static function formatForUser($datetime, string $format = 'M j, Y g:i A'): ?string
    {
        if (! $datetime) {
            return null;
        }

        $user = Auth::user();
        if (! $user || ! $user->timezone) {
            return Carbon::parse($datetime)->format($format);
        }

        return Carbon::parse($datetime)
            ->setTimezone($user->timezone)
            ->format($format);
    }

    /**
     * Format a datetime for a specific user's timezone
     */
    public static function formatForSpecificUser($datetime, $user, string $format = 'M j, Y g:i A'): ?string
    {
        if (! $datetime) {
            return null;
        }

        if (! $user || ! $user->timezone) {
            return Carbon::parse($datetime)->format($format);
        }

        return Carbon::parse($datetime)
            ->setTimezone($user->timezone)
            ->format($format);
    }

    /**
     * Convert a datetime to the current user's timezone
     */
    public static function convertForUser($datetime): ?Carbon
    {
        if (! $datetime) {
            return null;
        }

        $user = Auth::user();
        if (! $user || ! $user->timezone) {
            return Carbon::parse($datetime);
        }

        return Carbon::parse($datetime)->setTimezone($user->timezone);
    }

    /**
     * Get timezone-aware relative time (e.g., "2 hours ago")
     */
    public static function diffForUser($datetime): ?string
    {
        if (! $datetime) {
            return null;
        }

        $user = Auth::user();
        if (! $user || ! $user->timezone) {
            return Carbon::parse($datetime)->diffForHumans();
        }

        return Carbon::parse($datetime)
            ->setTimezone($user->timezone)
            ->diffForHumans();
    }

    /**
     * Get the current user's timezone or UTC as fallback
     */
    public static function getUserTimezone(): string
    {
        $user = Auth::user();

        return $user && $user->timezone ? $user->timezone : 'UTC';
    }

    /**
     * Get all available timezones grouped by region
     */
    public static function getGroupedTimezones(): array
    {
        $timezones = timezone_identifiers_list();
        $grouped = [];

        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone);
            $region = $parts[0];

            if (! isset($grouped[$region])) {
                $grouped[$region] = [];
            }

            $grouped[$region][$timezone] = $timezone;
        }

        // Sort regions and timezones
        ksort($grouped);
        foreach ($grouped as &$regionTimezones) {
            ksort($regionTimezones);
        }

        return $grouped;
    }

    /**
     * Get common timezones for quick selection
     */
    public static function getCommonTimezones(): array
    {
        return [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Shanghai' => 'Shanghai',
            'Asia/Kolkata' => 'Kolkata',
            'Australia/Sydney' => 'Sydney',
            'Pacific/Auckland' => 'Auckland',
        ];
    }
}
