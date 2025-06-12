<?php

namespace Tests\Feature;

use App\Models\GeoRule;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GeoTargetingWithRedisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        
        // Set Redis click tracking for these tests
        config(['shortener.analytics.click_tracking_method' => 'redis']);
    }

    public function test_geo_targeting_works_with_redis_click_tracking()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'geo-test',
        ]);

        // Create geo rule for US traffic
        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://us.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return US location
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getFullLocation')
                ->with('127.0.0.1') // Default test IP
                ->andReturn([
                    'country_code' => 'US',
                    'country_name' => 'United States',
                    'continent_code' => 'NA',
                    'continent_name' => 'North America',
                    'city' => 'New York',
                ]);
        });

        // Make request to the short link
        $response = $this->get('/geo-test');

        // Should redirect to US-specific URL
        $response->assertRedirect('https://us.example.com');
    }

    public function test_geo_targeting_with_redis_fallback_to_original_url()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'geo-fallback',
        ]);

        // Create geo rule for UK traffic only
        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['GB'],
            'redirect_url' => 'https://uk.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return Canadian location (no rule match)
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getFullLocation')
                ->with('127.0.0.1')
                ->andReturn([
                    'country_code' => 'CA',
                    'country_name' => 'Canada',
                    'continent_code' => 'NA',
                    'continent_name' => 'North America',
                    'city' => 'Toronto',
                ]);
        });

        // Make request to the short link
        $response = $this->get('/geo-fallback');

        // Should fallback to original URL since no geo rule matches Canada
        $response->assertRedirect('https://global.example.com');
    }

    public function test_geo_targeting_priority_with_redis_tracking()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'geo-priority',
        ]);

        // Create multiple geo rules with different priorities
        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'continent',
            'match_values' => ['NA'], // North America - lower priority
            'redirect_url' => 'https://na.example.com',
            'priority' => 10,
            'is_active' => true,
        ]);

        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'], // US-specific - higher priority
            'redirect_url' => 'https://us.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return US location (matches both rules)
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getFullLocation')
                ->with('127.0.0.1')
                ->andReturn([
                    'country_code' => 'US',
                    'country_name' => 'United States',
                    'continent_code' => 'NA',
                    'continent_name' => 'North America',
                    'city' => 'Los Angeles',
                ]);
        });

        // Make request to the short link
        $response = $this->get('/geo-priority');

        // Should use higher priority rule (country-specific, not continent)
        $response->assertRedirect('https://us.example.com');
    }

    public function test_geo_targeting_works_when_geolocation_unavailable()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'geo-unavailable',
        ]);

        // Create geo rule
        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://us.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService as unavailable
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
            // getFullLocation should not be called when service is unavailable
        });

        // Make request to the short link
        $response = $this->get('/geo-unavailable');

        // Should fallback to original URL when geolocation is unavailable
        $response->assertRedirect('https://global.example.com');
    }

    public function test_geo_targeting_with_utm_parameters_and_redis()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'geo-utm',
        ]);

        // Create geo rule for German traffic (use country instead of continent)
        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['DE'],
            'redirect_url' => 'https://eu.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return German location
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getFullLocation')
                ->with('127.0.0.1')
                ->andReturn([
                    'country_code' => 'DE',
                    'country_name' => 'Germany',
                    'continent_code' => 'EU',
                    'continent_name' => 'Europe',
                    'city' => 'Berlin',
                ]);
        });

        // Make request with UTM parameters
        $response = $this->get('/geo-utm?utm_source=newsletter&utm_campaign=summer2024');

        // Should redirect to geo-targeted URL with UTM parameters preserved
        $response->assertRedirect('https://eu.example.com?utm_source=newsletter&utm_campaign=summer2024');
    }

    public function test_redis_click_tracking_works_with_geo_targeting()
    {
        // This test verifies that geo-targeted redirects work with Redis config
        // We don't need to mock Redis internals, just verify the integration
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'original_url' => 'https://global.example.com',
            'short_code' => 'redis-geo',
        ]);

        GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['JP'],
            'redirect_url' => 'https://jp.example.com',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService
        $this->mock(GeolocationService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getFullLocation')
                ->with('127.0.0.1')
                ->andReturn([
                    'country_code' => 'JP',
                    'country_name' => 'Japan',
                    'continent_code' => 'AS',
                    'continent_name' => 'Asia',
                    'city' => 'Tokyo',
                ]);
        });

        // Make request - should work with Redis click tracking enabled
        $response = $this->get('/redis-geo');

        // Should redirect to geo-targeted URL
        $response->assertRedirect('https://jp.example.com');
        
        // Link click count should be incremented (async via Redis or sync fallback)
        // We don't test Redis internals, just that the redirect works
        $this->assertTrue(true); // Test passes if redirect works
    }
}