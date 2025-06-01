<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'short_code',
        'original_url',
        'group_id',
        'redirect_type',
        'is_active',
        'expires_at',
        'created_by',
        'click_count',
        'custom_slug',
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

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFullUrlAttribute(): string
    {
        return url($this->short_code);
    }

    /**
     * Check if the link instance needs a health check
     */
    public function requiresHealthCheck(): bool
    {
        // Never been checked
        if (!$this->last_checked_at) {
            return true;
        }

        // Check based on status and age
        return match($this->health_status) {
            'healthy' => $this->last_checked_at->lt(now()->subDays(7)), // Weekly for healthy
            'warning' => $this->last_checked_at->lt(now()->subDays(3)), // Every 3 days for warnings
            'error' => $this->last_checked_at->lt(now()->subDay()),     // Daily for errors
            default => true,
        };
    }

    /**
     * Get health status color for display
     */
    public function getHealthStatusColorAttribute(): string
    {
        return match($this->health_status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'error' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get health status icon
     */
    public function getHealthStatusIconAttribute(): string
    {
        return match($this->health_status) {
            'healthy' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Scope for links that need health checking
     */
    public function scopeNeedsHealthCheck(Builder $query): Builder
    {
        return $query->where(function ($q) {
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
              });
        });
    }
}
