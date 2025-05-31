<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GeolocationService
{
    private $reader = null;
    
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
    
    public function isAvailable(): bool
    {
        return $this->reader !== null;
    }
}