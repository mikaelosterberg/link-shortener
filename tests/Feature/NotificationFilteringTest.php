<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    public function test_only_404_links_included_when_configured_for_404_only()
    {
        // Configure to only notify on 404 errors
        Cache::put('health_check.notify_on_status_codes', ['404'], 3600);
        Cache::put('health_check.max_notifications_per_link', 3, 3600);
        Cache::put('health_check.notification_cooldown_hours', 24, 3600);

        // Create various failed links
        $link404 = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => 404,
            'health_check_message' => 'Page not found',
            'notification_count' => 0,
        ]);

        $linkTimeout = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => null,
            'health_check_message' => 'Health check job failed (timeout)',
            'notification_count' => 0,
        ]);

        $link500 = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => 500,
            'health_check_message' => 'Server error',
            'notification_count' => 0,
        ]);

        $linkConnectionFailed = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => null,
            'health_check_message' => 'Failed to connect',
            'notification_count' => 0,
        ]);

        // Run the command in dry-run mode to check what would be sent
        $this->artisan('notifications:send-health', ['--dry-run' => true])
            ->expectsOutputToContain('Found 1 newly failed links')
            ->expectsOutputToContain($link404->original_url)
            ->doesntExpectOutputToContain($linkTimeout->original_url)
            ->doesntExpectOutputToContain($link500->original_url)
            ->doesntExpectOutputToContain($linkConnectionFailed->original_url)
            ->assertExitCode(0);
    }

    public function test_timeout_links_included_when_timeout_selected()
    {
        // Configure to notify on 404 and timeout
        Cache::put('health_check.notify_on_status_codes', ['404', 'timeout'], 3600);
        Cache::put('health_check.max_notifications_per_link', 3, 3600);
        Cache::put('health_check.notification_cooldown_hours', 24, 3600);

        // Create test links
        $link404 = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => 404,
            'health_check_message' => 'Page not found',
            'notification_count' => 0,
        ]);

        $linkTimeout = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => null,
            'health_check_message' => 'Health check job failed (timeout)',
            'notification_count' => 0,
        ]);

        // Run the command in dry-run mode
        $this->artisan('notifications:send-health', ['--dry-run' => true])
            ->expectsOutputToContain('Found 2 newly failed links')
            ->expectsOutputToContain($link404->original_url)
            ->expectsOutputToContain($linkTimeout->original_url)
            ->assertExitCode(0);
    }

    public function test_connection_failed_links_included_when_selected()
    {
        // Configure to notify on connection failures only
        Cache::put('health_check.notify_on_status_codes', ['connection_failed'], 3600);
        Cache::put('health_check.max_notifications_per_link', 3, 3600);
        Cache::put('health_check.notification_cooldown_hours', 24, 3600);

        // Create test links
        $linkConnectionFailed = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => null,
            'health_check_message' => 'Failed to connect',
            'notification_count' => 0,
        ]);

        $link404 = Link::factory()->create([
            'created_by' => $this->user->id,
            'is_active' => true,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
            'health_status' => 'error',
            'http_status_code' => 404,
            'health_check_message' => 'Page not found',
            'notification_count' => 0,
        ]);

        // Run the command in dry-run mode
        $this->artisan('notifications:send-health', ['--dry-run' => true])
            ->expectsOutputToContain('Found 1 newly failed links')
            ->expectsOutputToContain($linkConnectionFailed->original_url)
            ->doesntExpectOutputToContain($link404->original_url)
            ->assertExitCode(0);
    }
}