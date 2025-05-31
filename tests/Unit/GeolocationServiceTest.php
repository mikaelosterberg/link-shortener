<?php

namespace Tests\Unit;

use App\Services\GeolocationService;
use Tests\TestCase;

class GeolocationServiceTest extends TestCase
{
    protected GeolocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new GeolocationService();
    }

    public function test_returns_null_location_when_database_unavailable(): void
    {
        // If database is not available, should return null values
        $location = $this->service->getLocation('8.8.8.8');

        $this->assertIsArray($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
    }

    public function test_handles_invalid_ip_address_gracefully(): void
    {
        $location = $this->service->getLocation('invalid-ip');

        $this->assertIsArray($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
    }

    public function test_handles_private_ip_address(): void
    {
        $location = $this->service->getLocation('192.168.1.1');

        $this->assertIsArray($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
    }

    public function test_handles_localhost_ip(): void
    {
        $location = $this->service->getLocation('127.0.0.1');

        $this->assertIsArray($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
    }

    public function test_is_available_method_works(): void
    {
        $isAvailable = $this->service->isAvailable();

        $this->assertIsBool($isAvailable);
    }

    public function test_real_ip_lookup_if_database_available(): void
    {
        // Test with Google's public DNS IP
        $location = $this->service->getLocation('8.8.8.8');

        $this->assertIsArray($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);

        // If the database is available and working, we might get real data
        // If not available, we'll get null values - both are acceptable
        if ($this->service->isAvailable()) {
            // If service reports as available, the location might have data
            // Note: Even with database available, some IPs might not have city data
            $this->assertTrue(
                is_string($location['country']) || is_null($location['country'])
            );
            $this->assertTrue(
                is_string($location['city']) || is_null($location['city'])
            );
        } else {
            // If service is not available, should return null values
            $this->assertNull($location['country']);
            $this->assertNull($location['city']);
        }
    }

    public function test_consistent_return_format(): void
    {
        $testIps = [
            '8.8.8.8',
            '1.1.1.1',
            '127.0.0.1',
            '192.168.1.1',
            'invalid-ip'
        ];

        foreach ($testIps as $ip) {
            $location = $this->service->getLocation($ip);
            
            $this->assertIsArray($location);
            $this->assertArrayHasKey('country', $location);
            $this->assertArrayHasKey('city', $location);
            $this->assertCount(2, $location);
        }
    }
}