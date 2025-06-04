<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UtmParameterTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }
    
    public function test_utm_parameters_are_passed_through_to_destination_url()
    {
        // Create a link
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test123',
            'original_url' => 'https://example.com/page',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with UTM parameters
        $response = $this->get('/test123?utm_source=newsletter&utm_medium=email&utm_campaign=spring2024');
        
        // Should redirect to destination URL with UTM parameters
        $response->assertRedirect('https://example.com/page?utm_source=newsletter&utm_medium=email&utm_campaign=spring2024');
    }
    
    public function test_utm_parameters_merge_with_existing_query_parameters()
    {
        // Create a link with query parameters
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test456',
            'original_url' => 'https://example.com/page?ref=homepage&version=2',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with UTM parameters
        $response = $this->get('/test456?utm_source=social&utm_medium=twitter');
        
        // Should merge parameters
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('location');
        
        // Parse URL to check parameters
        $parsedUrl = parse_url($redirectUrl);
        parse_str($parsedUrl['query'], $params);
        
        $this->assertEquals('homepage', $params['ref']);
        $this->assertEquals('2', $params['version']);
        $this->assertEquals('social', $params['utm_source']);
        $this->assertEquals('twitter', $params['utm_medium']);
    }
    
    public function test_utm_parameters_override_existing_utm_parameters()
    {
        // Create a link with existing UTM parameters
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test789',
            'original_url' => 'https://example.com/page?utm_source=default&utm_campaign=old',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with new UTM parameters
        $response = $this->get('/test789?utm_source=newsletter&utm_medium=email');
        
        // Should override existing UTM parameters
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('location');
        
        // Parse URL to check parameters
        $parsedUrl = parse_url($redirectUrl);
        parse_str($parsedUrl['query'], $params);
        
        $this->assertEquals('newsletter', $params['utm_source']); // Overridden
        $this->assertEquals('email', $params['utm_medium']); // Added
        $this->assertEquals('old', $params['utm_campaign']); // Preserved
    }
    
    public function test_only_valid_utm_parameters_are_processed()
    {
        // Create a link
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testvalid',
            'original_url' => 'https://example.com/page',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with valid and invalid parameters
        $response = $this->get('/testvalid?utm_source=newsletter&utm_medium=email&invalid_param=test&another=value');
        
        // Should only pass through UTM parameters
        $response->assertRedirect('https://example.com/page?utm_source=newsletter&utm_medium=email');
    }
    
    public function test_utm_parameters_are_stored_in_click_analytics()
    {
        Queue::fake();
        
        // Create a link
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testanalytics',
            'original_url' => 'https://example.com/page',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with UTM parameters
        $this->get('/testanalytics?utm_source=newsletter&utm_medium=email&utm_campaign=spring2024&utm_term=shoes&utm_content=banner');
        
        // Check that LogClickJob was dispatched with UTM data
        Queue::assertPushed(\App\Jobs\LogClickJob::class, function ($job) {
            // Use reflection to access protected property
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('clickData');
            $property->setAccessible(true);
            $clickData = $property->getValue($job);
            
            return $clickData['utm_source'] === 'newsletter' &&
                   $clickData['utm_medium'] === 'email' &&
                   $clickData['utm_campaign'] === 'spring2024' &&
                   $clickData['utm_term'] === 'shoes' &&
                   $clickData['utm_content'] === 'banner';
        });
    }
    
    public function test_utm_parameters_work_with_geo_targeting()
    {
        // Create a link with geo rules and test that UTM parameters still work
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testgeo',
            'original_url' => 'https://example.com/default',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Create geo rule (this will redirect to different URL, but UTM should still work)
        \App\Models\GeoRule::create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US'],
            'redirect_url' => 'https://example.com/us-page',
            'priority' => 1,
            'is_active' => true,
        ]);
        
        // Mock GeolocationService to return US location
        $geoMock = \Mockery::mock(\App\Services\GeolocationService::class);
        $geoMock->shouldReceive('isAvailable')->andReturn(true);
        $geoMock->shouldReceive('getFullLocation')->andReturn([
            'country_code' => 'US',
            'country_name' => 'United States',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'city' => 'New York'
        ]);
        
        $this->app->instance(\App\Services\GeolocationService::class, $geoMock);
        
        // Make request with UTM parameters
        $response = $this->get('/testgeo?utm_source=newsletter&utm_medium=email');
        
        // Should redirect to geo-targeted URL with UTM parameters
        $response->assertRedirect('https://example.com/us-page?utm_source=newsletter&utm_medium=email');
    }
    
    public function test_empty_utm_parameters_are_ignored()
    {
        // Create a link
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testempty',
            'original_url' => 'https://example.com/page',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request with empty UTM parameters
        $response = $this->get('/testempty?utm_source=&utm_medium=email&utm_campaign=');
        
        // Should only include non-empty UTM parameters
        $response->assertRedirect('https://example.com/page?utm_medium=email');
    }
    
    public function test_no_utm_parameters_works_normally()
    {
        // Create a link
        $user = User::factory()->create();
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'testnormal',
            'original_url' => 'https://example.com/page',
            'group_id' => $group->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        
        // Make request without UTM parameters
        $response = $this->get('/testnormal');
        
        // Should redirect normally
        $response->assertRedirect('https://example.com/page');
    }
    
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
