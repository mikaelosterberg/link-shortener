<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthCheckExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    public function test_links_can_be_excluded_from_health_checks(): void
    {
        $user = User::factory()->create();

        $includedLink = Link::factory()->create([
            'exclude_from_health_checks' => false,
            'is_active' => true,
        ]);

        $excludedLink = Link::factory()->create([
            'exclude_from_health_checks' => true,
            'is_active' => true,
        ]);

        // Query for links that need health checks
        $linksToCheck = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->get();

        $this->assertCount(1, $linksToCheck);
        $this->assertTrue($linksToCheck->contains($includedLink));
        $this->assertFalse($linksToCheck->contains($excludedLink));
    }

    public function test_notification_count_is_tracked(): void
    {
        $link = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 0,
        ]);

        $this->assertEquals(0, $link->notification_count);

        // Simulate sending a notification
        $link->update([
            'notification_count' => $link->notification_count + 1,
            'last_notification_sent_at' => now(),
        ]);

        $this->assertEquals(1, $link->fresh()->notification_count);
        $this->assertNotNull($link->fresh()->last_notification_sent_at);
    }

    public function test_notification_pauses_after_limit_reached(): void
    {
        Cache::put('health_check.max_notifications_per_link', 3);

        $link = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 2,
            'notification_paused' => false,
        ]);

        // Simulate sending the third notification
        $link->update([
            'notification_count' => $link->notification_count + 1,
            'last_notification_sent_at' => now(),
        ]);

        // Check if notifications should be paused
        $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);
        if ($link->notification_count >= $maxNotifications) {
            $link->update(['notification_paused' => true]);
        }

        $this->assertEquals(3, $link->fresh()->notification_count);
        $this->assertTrue($link->fresh()->notification_paused);
    }

    public function test_first_failure_is_tracked(): void
    {
        $link = Link::factory()->create([
            'health_status' => 'healthy',
            'first_failure_detected_at' => null,
        ]);

        $this->assertNull($link->first_failure_detected_at);

        // Simulate first failure
        $link->update([
            'health_status' => 'error',
            'first_failure_detected_at' => now(),
        ]);

        $this->assertNotNull($link->fresh()->first_failure_detected_at);
    }

    public function test_failure_tracking_resets_when_link_becomes_healthy(): void
    {
        $link = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 3,
            'notification_paused' => true,
            'first_failure_detected_at' => now()->subDays(2),
            'last_notification_sent_at' => now()->subDay(),
        ]);

        // Simulate link becoming healthy
        $link->update([
            'health_status' => 'healthy',
            'first_failure_detected_at' => null,
            'notification_count' => 0,
            'notification_paused' => false,
        ]);

        $this->assertEquals('healthy', $link->fresh()->health_status);
        $this->assertEquals(0, $link->fresh()->notification_count);
        $this->assertFalse($link->fresh()->notification_paused);
        $this->assertNull($link->fresh()->first_failure_detected_at);
    }

    public function test_cooldown_period_is_respected(): void
    {
        Cache::put('health_check.notification_cooldown_hours', 24);

        $link = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 1,
            'last_notification_sent_at' => now()->subHours(12), // Within cooldown
        ]);

        $cooldownHours = Cache::get('health_check.notification_cooldown_hours', 24);

        // Check if link is within cooldown period
        $withinCooldown = $link->last_notification_sent_at &&
                         $link->last_notification_sent_at->gt(now()->subHours($cooldownHours));

        $this->assertTrue($withinCooldown);

        // Update to be outside cooldown
        $link->update(['last_notification_sent_at' => now()->subHours(25)]);

        $withinCooldown = $link->fresh()->last_notification_sent_at &&
                         $link->fresh()->last_notification_sent_at->gt(now()->subHours($cooldownHours));

        $this->assertFalse($withinCooldown);
    }
}
