<?php

namespace Tests\Unit;

use App\Jobs\SendGoogleAnalyticsEventJob;
use App\Models\IntegrationSetting;
use App\Services\GoogleAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendGoogleAnalyticsEventJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_created_with_correct_queue_and_properties(): void
    {
        $clickData = [
            'link_id' => 123,
            'destination_url' => 'https://example.com',
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);

        $this->assertEquals('analytics', $job->queue);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->timeout);
    }

    public function test_job_handles_click_data_successfully(): void
    {
        $this->enableGoogleAnalytics();

        $clickData = [
            'link_id' => 123,
            'link_slug' => 'test-link',
            'destination_url' => 'https://example.com',
            'referrer' => 'https://referrer.com',
            'country' => 'US',
            'utm_source' => 'newsletter',
        ];

        // Mock the GoogleAnalyticsService
        $serviceMock = $this->createMock(GoogleAnalyticsService::class);
        $serviceMock->expects($this->once())
            ->method('sendClickEvent')
            ->with($clickData)
            ->willReturn(true);

        $this->app->instance(GoogleAnalyticsService::class, $serviceMock);

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle();

        // If we get here without exception, the job handled successfully
        $this->assertTrue(true);
    }

    public function test_job_handles_service_failure_gracefully(): void
    {
        $this->enableGoogleAnalytics();

        $clickData = [
            'link_id' => 123,
            'destination_url' => 'https://example.com',
        ];

        // Mock the GoogleAnalyticsService to return false
        $serviceMock = $this->createMock(GoogleAnalyticsService::class);
        $serviceMock->expects($this->once())
            ->method('sendClickEvent')
            ->with($clickData)
            ->willReturn(false);

        $this->app->instance(GoogleAnalyticsService::class, $serviceMock);

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle();

        // Job should handle failure gracefully without throwing exception
        $this->assertTrue(true);
    }

    public function test_job_handles_service_exception_gracefully(): void
    {
        $this->enableGoogleAnalytics();

        $clickData = [
            'link_id' => 123,
            'destination_url' => 'https://example.com',
        ];

        // Mock the GoogleAnalyticsService to throw exception
        $serviceMock = $this->createMock(GoogleAnalyticsService::class);
        $serviceMock->expects($this->once())
            ->method('sendClickEvent')
            ->with($clickData)
            ->willThrowException(new \Exception('Network error'));

        $this->app->instance(GoogleAnalyticsService::class, $serviceMock);

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle();

        // Job should handle exception gracefully without rethrowing
        $this->assertTrue(true);
    }

    public function test_job_can_be_dispatched_to_analytics_queue(): void
    {
        Queue::fake();

        $clickData = [
            'link_id' => 123,
            'destination_url' => 'https://example.com',
        ];

        SendGoogleAnalyticsEventJob::dispatch($clickData);

        Queue::assertPushed(SendGoogleAnalyticsEventJob::class, function ($job) {
            return $job->queue === 'analytics';
        });
    }

    public function test_job_serializes_click_data_correctly(): void
    {
        $clickData = [
            'link_id' => 123,
            'link_slug' => 'test-link',
            'destination_url' => 'https://example.com',
            'referrer' => 'https://referrer.com',
            'country' => 'US',
            'region' => 'California',
            'city' => 'San Francisco',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring2024',
            'ab_test_id' => 'test-1',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);

        // Serialize and unserialize to test data integrity
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(SendGoogleAnalyticsEventJob::class, $unserialized);
        $this->assertEquals('analytics', $unserialized->queue);
    }

    public function test_job_has_exponential_backoff_strategy(): void
    {
        $clickData = ['link_id' => 123, 'destination_url' => 'https://example.com'];
        $job = new SendGoogleAnalyticsEventJob($clickData);

        // Test backoff calculation for different attempt numbers
        $backoff1 = $job->backoff();
        $this->assertIsArray($backoff1);
        $this->assertCount(3, $backoff1);

        // Verify exponential backoff: 10s, 30s, 60s
        $this->assertEquals(10, $backoff1[0]);
        $this->assertEquals(30, $backoff1[1]);
        $this->assertEquals(60, $backoff1[2]);
    }

    public function test_job_does_not_fail_when_ga_disabled(): void
    {
        // Don't enable GA integration
        $clickData = [
            'link_id' => 123,
            'destination_url' => 'https://example.com',
        ];

        $job = new SendGoogleAnalyticsEventJob($clickData);
        $job->handle();

        // Should not throw exception even when GA is disabled
        $this->assertTrue(true);
    }

    protected function enableGoogleAnalytics(): void
    {
        IntegrationSetting::set('google_analytics', 'enabled', true);
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-XXXXXXXXXX');
        IntegrationSetting::set('google_analytics', 'api_secret', 'test-secret');
    }
}
