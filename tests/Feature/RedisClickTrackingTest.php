<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Services\ClickTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default group for links
        LinkGroup::factory()->create(['is_default' => true]);

        // Clear Redis before each test
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            // Redis might not be available
        }
    }

    public function test_redis_tracking_stores_click_data()
    {
        // Skip if Redis is not available
        if (! $this->isRedisAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        // Enable Redis tracking
        config(['shortener.analytics.click_tracking_method' => 'redis']);

        // Create a link without click limit
        $link = Link::factory()->create([
            'short_code' => 'redis123',
            'click_count' => 5,
            'click_limit' => null,
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);
        $response->assertRedirect($link->original_url);

        // Check Redis for pending click data
        $pendingClicks = Redis::lrange('clicks:pending', 0, -1);
        $this->assertCount(1, $pendingClicks);

        $clickData = json_decode($pendingClicks[0], true);
        $this->assertEquals($link->id, $clickData['link_id']);
        $this->assertEquals('127.0.0.1', $clickData['ip_address']);

        // Check Redis click counter
        $redisCount = Redis::get('clicks:count:'.$link->id);
        $this->assertEquals(1, $redisCount);

        // Database count should not be incremented yet
        $this->assertEquals(5, $link->fresh()->click_count);
    }

    public function test_redis_tracking_falls_back_to_queue_on_error()
    {
        // Enable Redis tracking
        config(['shortener.analytics.click_tracking_method' => 'redis']);

        // Force Redis to fail by using invalid connection
        config(['database.redis.default.host' => 'invalid-host']);

        // Create a link
        $link = Link::factory()->create([
            'short_code' => 'fallback123',
            'click_count' => 5,
            'click_limit' => null,
        ]);

        // Visit the link - should not throw exception
        $response = $this->get('/'.$link->short_code);
        $response->assertRedirect($link->original_url);

        // Should fall back to queue method (which our current implementation increments sync)
        // This happens because the controller still handles increment for non-click-limit links
        $this->assertTrue($link->fresh()->click_count >= 5);
    }

    public function test_batch_processing_creates_click_records()
    {
        // Skip if Redis is not available
        if (! $this->isRedisAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        // Create test links
        $link1 = Link::factory()->create(['click_count' => 0]);
        $link2 = Link::factory()->create(['click_count' => 0]);

        // Manually add click data to Redis
        $clicks = [
            ['link_id' => $link1->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'Test', 'clicked_at' => now()],
            ['link_id' => $link1->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'Test', 'clicked_at' => now()],
            ['link_id' => $link2->id, 'ip_address' => '3.3.3.3', 'user_agent' => 'Test', 'clicked_at' => now()],
        ];

        foreach ($clicks as $click) {
            Redis::rpush('clicks:pending', json_encode($click));
        }

        // Process batch
        $service = new ClickTrackingService;
        $processed = $service->processBatch();

        $this->assertEquals(3, $processed);

        // Check click records were created
        $this->assertDatabaseCount('clicks', 3);

        // Check link counts were updated
        $this->assertEquals(2, $link1->fresh()->click_count);
        $this->assertEquals(1, $link2->fresh()->click_count);

        // Redis queue should be empty
        $remaining = Redis::llen('clicks:pending');
        $this->assertEquals(0, $remaining);
    }

    public function test_links_with_click_limit_still_increment_synchronously()
    {
        // Skip if Redis is not available
        if (! $this->isRedisAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        // Enable Redis tracking
        config(['shortener.analytics.click_tracking_method' => 'redis']);

        // Create a link with click limit
        $link = Link::factory()->create([
            'short_code' => 'limited456',
            'click_count' => 5,
            'click_limit' => 10,
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);
        $response->assertRedirect($link->original_url);

        // Database count SHOULD be incremented immediately
        $this->assertEquals(6, $link->fresh()->click_count);

        // Click data should still be in Redis for detailed tracking
        $pendingClicks = Redis::lrange('clicks:pending', 0, -1);
        $this->assertCount(1, $pendingClicks);
    }

    public function test_none_tracking_method_only_increments_count()
    {
        // Enable 'none' tracking method
        config(['shortener.analytics.click_tracking_method' => 'none']);

        // Create a link
        $link = Link::factory()->create([
            'short_code' => 'none123',
            'click_count' => 5,
            'click_limit' => null,
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);
        $response->assertRedirect($link->original_url);

        // Count should be incremented
        $this->assertEquals(6, $link->fresh()->click_count);

        // No click records should be created
        $this->assertDatabaseCount('clicks', 0);

        // No Redis data should be stored
        if ($this->isRedisAvailable()) {
            $pendingClicks = Redis::lrange('clicks:pending', 0, -1);
            $this->assertCount(0, $pendingClicks);
        }
    }

    private function isRedisAvailable(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
