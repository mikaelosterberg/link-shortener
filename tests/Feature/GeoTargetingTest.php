<?php

namespace Tests\Feature;

use App\Models\GeoRule;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class GeoTargetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    public function test_redirect_uses_geo_rule_for_matching_country()
    {
        // Create a link with geo rules
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test123',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create geo rule for US visitors
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-visitors',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return US location
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'US',
            'country_name' => 'United States',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'city' => 'New York',
        ]);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/test123');

        // Should redirect to US-specific URL
        $response->assertRedirect('https://example.com/us-visitors');
    }

    public function test_redirect_uses_default_url_when_no_geo_match()
    {
        // Create a link with geo rules
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test456',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create geo rule for US visitors only
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-visitors',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return CA location
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'CA',
            'country_name' => 'Canada',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'city' => 'Toronto',
        ]);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/test456');

        // Should redirect to default URL
        $response->assertRedirect('https://example.com/default');
    }

    public function test_redirect_respects_geo_rule_priority()
    {
        // Create a link with multiple geo rules
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test789',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create geo rules with different priorities
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'continent',
            'match_values' => ['NA'],
            'redirect_url' => 'https://example.com/north-america',
            'priority' => 10,
            'is_active' => true,
        ]);

        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-specific',
            'priority' => 5,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return US location
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'US',
            'country_name' => 'United States',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'city' => 'Los Angeles',
        ]);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/test789');

        // Should redirect to US-specific URL (lower priority number = higher priority)
        $response->assertRedirect('https://example.com/us-specific');
    }

    public function test_redirect_works_with_custom_regions()
    {
        // Create a link with region-based geo rule
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testregion',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create geo rule for GDPR zone
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'region',
            'match_values' => ['gdpr_zone'],
            'redirect_url' => 'https://example.com/gdpr-privacy',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService to return DE location
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'DE',
            'country_name' => 'Germany',
            'continent_code' => 'EU',
            'continent_name' => 'Europe',
            'city' => 'Berlin',
        ]);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/testregion');

        // Should redirect to GDPR-specific URL
        $response->assertRedirect('https://example.com/gdpr-privacy');
    }

    public function test_redirect_ignores_inactive_geo_rules()
    {
        // Create a link with inactive geo rule
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testinactive',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create inactive geo rule
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-visitors',
            'priority' => 1,
            'is_active' => false,
        ]);

        // Mock GeolocationService to return US location
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'US',
            'country_name' => 'United States',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'city' => 'Chicago',
        ]);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/testinactive');

        // Should redirect to default URL since rule is inactive
        $response->assertRedirect('https://example.com/default');
    }

    public function test_redirect_works_without_geolocation_service()
    {
        // Create a link with geo rules
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testnogeo',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Create geo rule
        GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-visitors',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Mock GeolocationService as unavailable
        $geoMock = Mockery::mock(GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(false);

        $this->app->instance(GeolocationService::class, $geoMock);

        // Make request
        $response = $this->get('/testnogeo');

        // Should redirect to default URL when geo service unavailable
        $response->assertRedirect('https://example.com/default');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
