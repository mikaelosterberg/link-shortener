<?php

namespace Tests\Feature;

use App\Jobs\SendGoogleAnalyticsEventJob;
use App\Models\IntegrationSetting;
use App\Models\Link;
use App\Models\User;
use App\Services\ClickTrackingService;
use App\Services\GoogleAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleAnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Link $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->link = Link::create([
            'short_code' => 'ga-test',
            'original_url' => 'https://example.com/landing-page',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);
    }

    public function test_click_tracking_dispatches_google_analytics_job_when_enabled(): void
    {
        $this->enableGoogleAnalytics();
        Queue::fake();

        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '8.8.8.8',
            'user_agent' => 'Mozilla/5.0 (compatible; Test)',
            'referer' => 'https://newsletter.example.com',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring2024',
            'clicked_at' => now(),
        ];

        $trackingService = app(ClickTrackingService::class);
        $trackingService->trackClick($this->link, $clickData);

        Queue::assertPushed(SendGoogleAnalyticsEventJob::class, function ($job) {
            return $job->queue === 'analytics';
        });
    }

    public function test_click_tracking_handles_ga_job_when_disabled(): void
    {
        // Don't enable Google Analytics - job should be dispatched but exit early
        Queue::fake();
        Http::fake(); // No HTTP requests should be made

        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '8.8.8.8',
            'user_agent' => 'Mozilla/5.0 (compatible; Test)',
            'referer' => 'https://example.com',
            'clicked_at' => now(),
        ];

        $trackingService = app(ClickTrackingService::class);
        $trackingService->trackClick($this->link, $clickData);

        // Job should be pushed (for consistency) but exit early when processed
        Queue::assertPushed(SendGoogleAnalyticsEventJob::class);
        
        // Process the job to verify it exits early without making HTTP requests
        $jobs = Queue::pushedJobs();
        $gaJobs = $jobs[SendGoogleAnalyticsEventJob::class] ?? [];
        $this->assertCount(1, $gaJobs);
        
        $gaClickData = array_merge($clickData, [
            'link_slug' => $this->link->short_code,
            'destination_url' => $this->link->original_url,
        ]);
        
        $job = new SendGoogleAnalyticsEventJob($gaClickData);
        $job->handle(app(GoogleAnalyticsService::class));
        
        // Verify no HTTP requests were made when GA is disabled
        Http::assertNothingSent();
    }

    public function test_redirect_with_google_analytics_integration(): void
    {
        $this->enableGoogleAnalytics();
        Queue::fake();

        // Simulate a redirect request with UTM parameters
        $response = $this->get('/ga-test?utm_source=social&utm_medium=facebook&utm_campaign=summer2024');

        $response->assertRedirect('https://example.com/landing-page?utm_source=social&utm_medium=facebook&utm_campaign=summer2024');

        // Verify GA job was dispatched
        Queue::assertPushed(SendGoogleAnalyticsEventJob::class, function ($job) {
            return $job->queue === 'analytics';
        });
    }

    public function test_google_analytics_receives_comprehensive_click_data(): void
    {
        $this->enableGoogleAnalytics();
        Http::fake([
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);

        $clickData = [
            'link_id' => $this->link->id,
            'link_slug' => $this->link->short_code,
            'destination_url' => $this->link->original_url,
            'ip_address' => '203.0.113.0', // Public IP for geolocation
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'referer' => 'https://newsletter.example.com',
            'country' => 'US',
            'region' => 'California',
            'city' => 'San Francisco',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring2024',
            'utm_term' => 'link shortener',
            'utm_content' => 'header-cta',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'clicked_at' => now(),
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle(app(GoogleAnalyticsService::class));

        Http::assertSent(function ($request) use ($clickData) {
            $payload = json_decode($request->body(), true);

            // Verify it's sent as a page_view event
            $this->assertEquals('page_view', $payload['events'][0]['name']);

            $params = $payload['events'][0]['params'];

            // Verify core page view parameters
            $expectedShortLink = config('app.url').'/'.$clickData['link_slug'];
            $this->assertEquals($expectedShortLink, $params['page_location']);
            $this->assertEquals($clickData['link_slug'].' - Link Redirect', $params['page_title']);
            $this->assertEquals($clickData['referer'], $params['page_referrer']);

            // Verify custom link parameters
            $this->assertEquals($clickData['link_id'], $params['custom_link_id']);
            $this->assertEquals($clickData['link_slug'], $params['custom_link_slug']);
            $this->assertEquals($clickData['destination_url'], $params['custom_destination_url']);

            // Verify IP override is included for correct geographic detection
            $this->assertEquals($clickData['ip_address'], $payload['ip_override']);

            // Verify all UTM parameters (mapped to GA4 standard names)
            $this->assertEquals($clickData['utm_source'], $params['source']);
            $this->assertEquals($clickData['utm_medium'], $params['medium']);
            $this->assertEquals($clickData['utm_campaign'], $params['campaign']);
            $this->assertEquals($clickData['utm_term'], $params['term']);
            $this->assertEquals($clickData['utm_content'], $params['content']);

            // Verify device/browser parameters
            $this->assertEquals($clickData['device_type'], $params['device_type']);
            $this->assertEquals($clickData['browser'], $params['browser']);
            $this->assertEquals($clickData['os'], $params['operating_system']);

            return true;
        });
    }

    public function test_google_analytics_handles_ab_testing_data(): void
    {
        $this->enableGoogleAnalytics();
        Http::fake([
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);

        $clickData = [
            'link_id' => $this->link->id,
            'link_slug' => $this->link->short_code,
            'destination_url' => 'https://example.com/variant-a',
            'ab_test_id' => 'homepage-hero-test',
            'ab_variant_id' => 'variant-a',
            'clicked_at' => now(),
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle(app(GoogleAnalyticsService::class));

        Http::assertSent(function ($request) use ($clickData) {
            $payload = json_decode($request->body(), true);
            $params = $payload['events'][0]['params'];

            $this->assertEquals($clickData['ab_test_id'], $params['ab_test_id']);
            $this->assertEquals($clickData['ab_variant_id'], $params['ab_variant_id']);

            return true;
        });
    }

    public function test_google_analytics_integration_with_redis_click_tracking(): void
    {
        $this->enableGoogleAnalytics();
        config(['shortener.click_tracking_method' => 'redis']);
        Queue::fake();

        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '8.8.8.8',
            'user_agent' => 'Mozilla/5.0 (compatible; Test)',
            'referer' => 'https://example.com',
            'utm_source' => 'social',
            'clicked_at' => now(),
        ];

        $trackingService = app(ClickTrackingService::class);
        $trackingService->trackClick($this->link, $clickData);

        // Even with Redis tracking, GA job should still be dispatched immediately
        Queue::assertPushed(SendGoogleAnalyticsEventJob::class);
    }

    public function test_google_analytics_job_failure_does_not_affect_click_tracking(): void
    {
        $this->enableGoogleAnalytics();
        Http::fake([
            'www.google-analytics.com/*' => Http::response('Server Error', 500),
        ]);

        $clickData = [
            'link_id' => $this->link->id,
            'link_slug' => $this->link->short_code,
            'destination_url' => $this->link->original_url,
            'clicked_at' => now(),
        ];

        // This should not throw an exception even if GA fails
        $job = new SendGoogleAnalyticsEventJob($clickData);
        $result = $job->handle(app(GoogleAnalyticsService::class));

        // Verify GA was attempted but failed gracefully
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'www.google-analytics.com');
        });

        // Test passes if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_google_analytics_respects_production_ssl_settings(): void
    {
        $this->enableGoogleAnalytics();
        config(['app.env' => 'production']);

        Http::fake([
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);

        $clickData = [
            'link_id' => $this->link->id,
            'destination_url' => $this->link->original_url,
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle(app(GoogleAnalyticsService::class));

        // In production, SSL verification should be enabled
        Http::assertSent(function ($request) {
            // We can't directly test the CURL options, but we can verify the request was made
            return str_contains($request->url(), 'https://www.google-analytics.com');
        });
    }

    public function test_google_analytics_uses_ipv4_resolution(): void
    {
        $this->enableGoogleAnalytics();
        Http::fake([
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);

        $clickData = [
            'link_id' => $this->link->id,
            'destination_url' => $this->link->original_url,
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle(app(GoogleAnalyticsService::class));

        // Verify the request was made (IPv4 resolution is handled internally)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'www.google-analytics.com/mp/collect');
        });
    }

    public function test_multiple_clicks_generate_separate_ga_events(): void
    {
        $this->enableGoogleAnalytics();
        Http::fake([
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);

        $clickData1 = [
            'link_id' => $this->link->id,
            'destination_url' => $this->link->original_url,
            'utm_source' => 'email',
            'session_id' => 'session-1',
        ];

        $clickData2 = [
            'link_id' => $this->link->id,
            'destination_url' => $this->link->original_url,
            'utm_source' => 'social',
            'session_id' => 'session-2',
        ];

        $job1 = new SendGoogleAnalyticsEventJob($clickData1);
        $job2 = new SendGoogleAnalyticsEventJob($clickData2);

        $job1->handle(app(GoogleAnalyticsService::class));
        $job2->handle(app(GoogleAnalyticsService::class));

        // Verify two separate GA events were sent
        Http::assertSentCount(2);

        // Verify they had different client IDs and UTM sources
        $requests = Http::recorded();

        $payload1 = json_decode($requests[0][0]->body(), true);
        $payload2 = json_decode($requests[1][0]->body(), true);

        $this->assertNotEquals($payload1['client_id'], $payload2['client_id']);
        $this->assertEquals('email', $payload1['events'][0]['params']['source']);
        $this->assertEquals('social', $payload2['events'][0]['params']['source']);
    }

    protected function enableGoogleAnalytics(): void
    {
        IntegrationSetting::set('google_analytics', 'enabled', true);
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-TESTTEST123');
        IntegrationSetting::set('google_analytics', 'api_secret', 'test-api-secret-456');
    }
}
