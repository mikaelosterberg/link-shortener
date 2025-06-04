<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GeolocationService
{
    private $reader = null;
    
    const CUSTOM_REGIONS = [
        'gdpr_zone' => ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'CH', 'NO', 'IS', 'LI'],
        'five_eyes' => ['US', 'CA', 'GB', 'AU', 'NZ'],
        'apac_developed' => ['JP', 'KR', 'SG', 'HK', 'TW'],
        'north_america' => ['US', 'CA', 'MX'],
        'south_america' => ['AR', 'BO', 'BR', 'CL', 'CO', 'EC', 'GY', 'PY', 'PE', 'SR', 'UY', 'VE'],
        'middle_east' => ['AE', 'BH', 'EG', 'IQ', 'IR', 'IL', 'JO', 'KW', 'LB', 'OM', 'QA', 'SA', 'SY', 'TR', 'YE'],
    ];
    
    public function __construct()
    {
        $dbPath = storage_path('app/geoip/GeoLite2-City.mmdb');
        
        if (file_exists($dbPath)) {
            try {
                // If geoip2/geoip2 package is installed
                if (class_exists('\GeoIp2\Database\Reader')) {
                    $this->reader = new \GeoIp2\Database\Reader($dbPath);
                }
            } catch (\Exception $e) {
                Log::warning('GeoIP database unavailable: ' . $e->getMessage());
                $this->reader = null;
            }
        }
    }
    
    public function getLocation(string $ip): array
    {
        if (!$this->reader) {
            return ['country' => null, 'city' => null];
        }
        
        try {
            $record = $this->reader->city($ip);
            return [
                'country' => $record->country->name,
                'city' => $record->city->name
            ];
        } catch (\Exception $e) {
            return ['country' => null, 'city' => null];
        }
    }
    
    public function getFullLocation(string $ip): array
    {
        if (!$this->reader) {
            return [
                'country_code' => null,
                'country_name' => null,
                'continent_code' => null,
                'continent_name' => null,
                'city' => null
            ];
        }
        
        try {
            $record = $this->reader->city($ip);
            return [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
                'continent_code' => $record->continent->code,
                'continent_name' => $record->continent->name,
                'city' => $record->city->name
            ];
        } catch (\Exception $e) {
            return [
                'country_code' => null,
                'country_name' => null,
                'continent_code' => null,
                'continent_name' => null,
                'city' => null
            ];
        }
    }
    
    public function isAvailable(): bool
    {
        return $this->reader !== null;
    }
}