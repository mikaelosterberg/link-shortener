<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbTestVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ab_test_id',
        'name',
        'url',
        'weight',
        'click_count',
        'conversion_count',
    ];

    protected $casts = [
        'weight' => 'integer',
        'click_count' => 'integer',
        'conversion_count' => 'integer',
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    public function incrementConversions(): void
    {
        $this->increment('conversion_count');
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->click_count === 0) {
            return 0.0;
        }

        return round(($this->conversion_count / $this->click_count) * 100, 2);
    }

    public function getWeightPercentageAttribute(): string
    {
        return $this->weight.'%';
    }
}
