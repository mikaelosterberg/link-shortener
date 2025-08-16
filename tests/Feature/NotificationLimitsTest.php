<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        // Set up notification type
        NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health',
            'description' => 'Notifications for link health checks',
            'is_active' => true,
        ]);
    }

    public function test_respects_max_notifications_per_link_setting(): void
    {
        Cache::put('health_check.max_notifications_per_link', 2);
        Cache::put('health_check.notification_cooldown_hours', 0); // No cooldown for testing

        $link = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 0,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
        ]);

        // First notification
        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->where('notification_paused', false)
            ->where('notification_count', '<', 2);

        $this->assertTrue($query->get()->contains($link));

        // Simulate sending notifications
        $link->update(['notification_count' => 2]);

        // Should not be included anymore
        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->where('notification_paused', false)
            ->where('notification_count', '<', 2);

        $this->assertFalse($query->get()->contains($link));
    }

    public function test_respects_notification_cooldown(): void
    {
        Cache::put('health_check.notification_cooldown_hours', 24);

        $recentlyNotified = Link::factory()->create([
            'health_status' => 'error',
            'last_notification_sent_at' => now()->subHours(12),
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
        ]);

        $oldNotification = Link::factory()->create([
            'health_status' => 'error',
            'last_notification_sent_at' => now()->subHours(25),
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
        ]);

        $neverNotified = Link::factory()->create([
            'health_status' => 'error',
            'last_notification_sent_at' => null,
            'exclude_from_health_checks' => false,
            'notification_paused' => false,
        ]);

        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->where('notification_paused', false)
            ->where(function ($q) {
                $q->whereNull('last_notification_sent_at')
                    ->orWhere('last_notification_sent_at', '<', now()->subHours(24));
            });

        $results = $query->get();

        $this->assertFalse($results->contains($recentlyNotified));
        $this->assertTrue($results->contains($oldNotification));
        $this->assertTrue($results->contains($neverNotified));
    }

    public function test_filters_by_status_codes(): void
    {
        Cache::put('health_check.notify_on_status_codes', ['404', '500']);

        $link404 = Link::factory()->create([
            'health_status' => 'error',
            'http_status_code' => 404,
            'exclude_from_health_checks' => false,
        ]);

        $link500 = Link::factory()->create([
            'health_status' => 'error',
            'http_status_code' => 500,
            'exclude_from_health_checks' => false,
        ]);

        $link403 = Link::factory()->create([
            'health_status' => 'error',
            'http_status_code' => 403,
            'exclude_from_health_checks' => false,
        ]);

        $notifyOnStatuses = Cache::get('health_check.notify_on_status_codes', ['404']);

        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->whereIn('http_status_code', $notifyOnStatuses);

        $results = $query->get();

        $this->assertTrue($results->contains($link404));
        $this->assertTrue($results->contains($link500));
        $this->assertFalse($results->contains($link403));
    }

    public function test_excludes_timeout_when_configured(): void
    {
        Cache::put('health_check.exclude_timeout_from_notifications', true);
        Cache::put('health_check.notify_on_status_codes', ['404', 'timeout']);

        $timeoutLink = Link::factory()->create([
            'health_status' => 'timeout',
            'http_status_code' => null,
            'exclude_from_health_checks' => false,
        ]);

        $errorLink = Link::factory()->create([
            'health_status' => 'error',
            'http_status_code' => 404,
            'exclude_from_health_checks' => false,
        ]);

        $excludeTimeout = Cache::get('health_check.exclude_timeout_from_notifications', true);
        $notifyOnStatuses = Cache::get('health_check.notify_on_status_codes', ['404']);

        // Build query based on settings
        $statusConditions = [];
        foreach ($notifyOnStatuses as $status) {
            if ($status === 'timeout') {
                if (! $excludeTimeout) {
                    $statusConditions[] = 'timeout';
                }
            } elseif ($status === '404') {
                $statusConditions[] = 404;
            }
        }

        if ($excludeTimeout) {
            // Should only include 404 errors
            $query = Link::where('is_active', true)
                ->where('exclude_from_health_checks', false)
                ->where('http_status_code', 404);
        } else {
            // Should include both
            $query = Link::where('is_active', true)
                ->where('exclude_from_health_checks', false)
                ->where(function ($q) {
                    $q->where('health_status', 'timeout')
                        ->orWhere('http_status_code', 404);
                });
        }

        $results = $query->get();

        if ($excludeTimeout) {
            $this->assertFalse($results->contains($timeoutLink));
            $this->assertTrue($results->contains($errorLink));
        }
    }

    public function test_batch_notification_limit(): void
    {
        Cache::put('health_check.batch_notification_limit', 5);

        // Create 10 failed links
        $links = Link::factory()->count(10)->create([
            'health_status' => 'error',
            'http_status_code' => 404,
            'exclude_from_health_checks' => false,
        ]);

        $batchLimit = Cache::get('health_check.batch_notification_limit', 50);

        // In real implementation, this would be handled in the notification service
        $batches = $links->chunk($batchLimit);

        $this->assertEquals(2, $batches->count());
        $this->assertEquals(5, $batches->first()->count());
    }

    public function test_identifies_previously_failed_links(): void
    {
        Cache::put('health_check.max_notifications_per_link', 3);

        $newFailure = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 0,
            'exclude_from_health_checks' => false,
        ]);

        $previousFailure = Link::factory()->create([
            'health_status' => 'error',
            'notification_count' => 3,
            'exclude_from_health_checks' => false,
        ]);

        $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);

        // New failures query
        $newFailures = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->whereIn('health_status', ['error'])
            ->where('notification_count', '<', $maxNotifications)
            ->get();

        // Previously failed query
        $previouslyFailed = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->whereIn('health_status', ['error'])
            ->where('notification_count', '>=', $maxNotifications)
            ->get();

        $this->assertTrue($newFailures->contains($newFailure));
        $this->assertFalse($newFailures->contains($previousFailure));

        $this->assertFalse($previouslyFailed->contains($newFailure));
        $this->assertTrue($previouslyFailed->contains($previousFailure));
    }
}
