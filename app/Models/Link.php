<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_code',
        'original_url',
        'custom_slug',
        'password',
        'click_limit',
        'group_id',
        'redirect_type',
        'is_active',
        'expires_at',
        'created_by',
        'click_count',
        'last_checked_at',
        'health_status',
        'http_status_code',
        'health_check_message',
        'final_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'click_count' => 'integer',
        'click_limit' => 'integer',
        'redirect_type' => 'integer',
        'http_status_code' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LinkGroup::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function geoRules(): HasMany
    {
        return $this->hasMany(GeoRule::class)->orderBy('priority');
    }

    public function abTest(): HasOne
    {
        return $this->hasOne(AbTest::class);
    }

    public function linkNotifications(): HasMany
    {
        return $this->hasMany(LinkNotification::class);
    }

    public function activeNotifications(): HasMany
    {
        return $this->linkNotifications()->where('is_active', true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFullUrlAttribute(): string
    {
        return url($this->short_code);
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    public function hasClickLimit(): bool
    {
        return $this->click_limit !== null && $this->click_limit > 0;
    }

    public function isClickLimitExceeded(): bool
    {
        return $this->hasClickLimit() && $this->click_count >= $this->click_limit;
    }

    public function getRemainingClicksAttribute(): int
    {
        if (! $this->hasClickLimit()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->click_limit - $this->click_count);
    }

    /**
     * Check if the link instance needs a health check
     */
    public function requiresHealthCheck(): bool
    {
        // Never been checked
        if (! $this->last_checked_at) {
            return true;
        }

        // Check based on status and age
        return match ($this->health_status) {
            'healthy' => $this->last_checked_at->lt(now()->subDays(7)), // Weekly for healthy
            'warning' => $this->last_checked_at->lt(now()->subDays(3)), // Every 3 days for warnings
            'error' => $this->last_checked_at->lt(now()->subDay()),     // Daily for errors
            'blocked' => $this->last_checked_at->lt(now()->subDays(7)), // Weekly for blocked (may be datacenter IP issue)
            default => true,
        };
    }

    /**
     * Get health status color for display
     */
    public function getHealthStatusColorAttribute(): string
    {
        return match ($this->health_status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'error' => 'danger',
            'blocked' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get health status icon
     */
    public function getHealthStatusIconAttribute(): string
    {
        return match ($this->health_status) {
            'healthy' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle',
            'blocked' => 'heroicon-o-shield-exclamation',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Scope for links that need health checking
     * Only includes active links by default
     */
    public function scopeNeedsHealthCheck(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                // Never checked
                $q->whereNull('last_checked_at')
                  // Or healthy links older than 7 days
                    ->orWhere(function ($q2) {
                        $q2->where('health_status', 'healthy')
                            ->where('last_checked_at', '<', now()->subDays(7));
                    })
                  // Or warning links older than 3 days
                    ->orWhere(function ($q2) {
                        $q2->where('health_status', 'warning')
                            ->where('last_checked_at', '<', now()->subDays(3));
                    })
                  // Or error links older than 1 day
                    ->orWhere(function ($q2) {
                        $q2->where('health_status', 'error')
                            ->where('last_checked_at', '<', now()->subDay());
                    })
                  // Or blocked links older than 7 days
                    ->orWhere(function ($q2) {
                        $q2->where('health_status', 'blocked')
                            ->where('last_checked_at', '<', now()->subDays(7));
                    });
            });
    }
}
