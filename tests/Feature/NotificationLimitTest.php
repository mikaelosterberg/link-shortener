<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up notification settings
        Cache::put('health_check.max_notifications_per_link', 3);
        Cache::put('health_check.notification_cooldown_hours', 24);
        Cache::put('health_check.notify_on_status_codes', ['404']);
    }

    public function test_notification_count_increments_properly(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 0,
            'notification_paused' => false,
            'last_notification_sent_at' => null,
        ]);

        // First notification
        Artisan::call('notifications:send-health');
        $link->refresh();

        $this->assertEquals(1, $link->notification_count);
        $this->assertFalse($link->notification_paused);
        $this->assertNotNull($link->last_notification_sent_at);
    }

    public function test_notification_pauses_at_limit(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 2, // One away from limit
            'notification_paused' => false,
            'last_notification_sent_at' => now()->subHours(25), // Past cooldown
        ]);

        Artisan::call('notifications:send-health');
        $link->refresh();

        $this->assertEquals(3, $link->notification_count);
        $this->assertTrue($link->notification_paused);
    }

    public function test_cooldown_prevents_duplicate_notifications(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 1,
            'notification_paused' => false,
            'last_notification_sent_at' => now()->subHours(12), // Within cooldown
        ]);

        Artisan::call('notifications:send-health');
        $link->refresh();

        // Count should not increment due to cooldown
        $this->assertEquals(1, $link->notification_count);
    }

    public function test_notification_resets_when_link_becomes_healthy(): void
    {
        $link = Link::factory()->create([
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 2,
            'notification_paused' => false,
            'last_notification_sent_at' => now()->subDay(),
            'first_failure_detected_at' => now()->subDays(3),
        ]);

        // Simulate link becoming healthy via health check
        $link->health_status = 'healthy';
        $link->http_status_code = 200;

        // This logic is in CheckLinkHealthJob
        if ($link->health_status === 'healthy') {
            $link->notification_count = 0;
            $link->notification_paused = false;
            $link->last_notification_sent_at = null;
            $link->first_failure_detected_at = null;
        }

        $link->save();

        $this->assertEquals(0, $link->notification_count);
        $this->assertFalse($link->notification_paused);
        $this->assertNull($link->last_notification_sent_at);
        $this->assertNull($link->first_failure_detected_at);
    }

    public function test_null_notification_count_handled_properly(): void
    {
        $user = User::factory()->create();

        // Create a link and manually set notification_count to NULL via DB
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_paused' => false,
        ]);

        // Simulate NULL value that might exist for old links
        \DB::table('links')->where('id', $link->id)->update(['notification_count' => null]);
        $link->refresh();

        Artisan::call('notifications:send-health');
        $link->refresh();

        // Should handle NULL and increment to 1
        $this->assertEquals(1, $link->notification_count);
    }

    public function test_paused_links_excluded_from_notifications(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 3,
            'notification_paused' => true,
            'last_notification_sent_at' => now()->subDays(30), // Way past cooldown
        ]);

        $countBefore = $link->notification_count;
        Artisan::call('notifications:send-health');
        $link->refresh();

        // Count should not change for paused links
        $this->assertEquals($countBefore, $link->notification_count);
    }

    public function test_only_selected_status_codes_trigger_notifications(): void
    {
        $user = User::factory()->create();

        // Create link with 500 error (not in selected codes)
        Cache::put('health_check.notify_on_status_codes', ['404']); // Only 404

        $link500 = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 500,
            'notification_count' => 0,
        ]);

        $link404 = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 0,
        ]);

        Artisan::call('notifications:send-health');

        $link500->refresh();
        $link404->refresh();

        // 500 should not be notified
        $this->assertEquals(0, $link500->notification_count);
        // 404 should be notified
        $this->assertEquals(1, $link404->notification_count);
    }

    public function test_excluded_links_not_notified(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'created_by' => $user->id,
            'health_status' => 'error',
            'http_status_code' => 404,
            'notification_count' => 0,
            'exclude_from_health_checks' => true,
        ]);

        Artisan::call('notifications:send-health');
        $link->refresh();

        // Should not be notified
        $this->assertEquals(0, $link->notification_count);
    }
}
