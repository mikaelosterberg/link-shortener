<?php

namespace App\Models;

use App\Services\GeolocationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoRule extends Model
{
    protected $fillable = [
        'link_id',
        'match_type',
        'match_values',
        'redirect_url',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'match_values' => 'array',
        'is_active' => 'boolean',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function matchesLocation(array $location): bool
    {
        if (! $this->is_active) {
            return false;
        }

        switch ($this->match_type) {
            case 'country':
                return in_array($location['country_code'], $this->match_values);

            case 'continent':
                return in_array($location['continent_code'], $this->match_values);

            case 'region':
                // Check if the country is in any of the specified custom regions
                foreach ($this->match_values as $region) {
                    if (isset(GeolocationService::CUSTOM_REGIONS[$region]) &&
                        in_array($location['country_code'], GeolocationService::CUSTOM_REGIONS[$region])) {
                        return true;
                    }
                }

                return false;

            default:
                return false;
        }
    }

    public function getMatchTypeDisplayAttribute(): string
    {
        return match ($this->match_type) {
            'country' => 'Countries',
            'continent' => 'Continents',
            'region' => 'Regions',
            default => ucfirst($this->match_type)
        };
    }

    public function getMatchValuesDisplayAttribute(): string
    {
        if ($this->match_type === 'region') {
            return implode(', ', $this->match_values);
        }

        return implode(', ', $this->match_values);
    }
}
