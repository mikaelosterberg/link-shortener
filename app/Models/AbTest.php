<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_id',
        'name',
        'description',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AbTestVariant::class)->orderBy('weight', 'desc');
    }

    public function isActiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        // Check start date
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        // Check end date
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function selectVariant(): ?AbTestVariant
    {
        if (! $this->isActiveNow()) {
            return null;
        }

        $variants = $this->variants;

        if ($variants->isEmpty()) {
            return null;
        }

        // Generate random number between 1 and 100
        $random = mt_rand(1, 100);
        $cumulative = 0;

        // Walk through variants and select based on weight
        foreach ($variants as $variant) {
            $cumulative += $variant->weight;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        // Fallback to first variant
        return $variants->first();
    }

    public function getTotalClicksAttribute(): int
    {
        return $this->variants->sum('click_count');
    }

    public function getTotalConversionsAttribute(): int
    {
        return $this->variants->sum('conversion_count');
    }

    public function getConversionRateAttribute(): float
    {
        $totalClicks = $this->total_clicks;

        if ($totalClicks === 0) {
            return 0.0;
        }

        return round(($this->total_conversions / $totalClicks) * 100, 2);
    }
}
