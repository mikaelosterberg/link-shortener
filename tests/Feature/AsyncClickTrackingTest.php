<?php

namespace Tests\Feature;

use App\Jobs\LogClickJob;
use App\Models\Link;
use App\Models\LinkGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsyncClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default group for links
        LinkGroup::factory()->create(['is_default' => true]);
    }

    public function test_links_without_click_limit_increment_asynchronously()
    {
        Queue::fake();

        // Create a link without click limit
        $link = Link::factory()->create([
            'short_code' => 'test123',
            'click_count' => 5,
            'click_limit' => null, // No limit
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);

        // Should redirect successfully
        $response->assertRedirect($link->original_url);

        // Click count should NOT be incremented yet (still 5)
        $this->assertEquals(5, $link->fresh()->click_count);

        // Job should be dispatched with increment flag
        Queue::assertPushed(LogClickJob::class, function ($job) use ($link) {
            $data = $this->getJobData($job);

            return $data['link_id'] === $link->id &&
                   $data['increment_click_count'] === true;
        });
    }

    public function test_links_with_click_limit_increment_synchronously()
    {
        Queue::fake();

        // Create a link with click limit
        $link = Link::factory()->create([
            'short_code' => 'limited123',
            'click_count' => 5,
            'click_limit' => 10, // Has a limit
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);

        // Should redirect successfully
        $response->assertRedirect($link->original_url);

        // Click count SHOULD be incremented immediately (now 6)
        $this->assertEquals(6, $link->fresh()->click_count);

        // Job should be dispatched WITHOUT increment flag
        Queue::assertPushed(LogClickJob::class, function ($job) use ($link) {
            $data = $this->getJobData($job);

            return $data['link_id'] === $link->id &&
                   ($data['increment_click_count'] ?? false) === false;
        });
    }

    public function test_click_limit_enforcement_remains_accurate()
    {
        Queue::fake();

        // Create a link at its click limit
        $link = Link::factory()->create([
            'short_code' => 'maxed123',
            'click_count' => 9,
            'click_limit' => 10, // One click remaining
        ]);

        // First visit should work
        $response = $this->get('/'.$link->short_code);
        $response->assertRedirect($link->original_url);
        $this->assertEquals(10, $link->fresh()->click_count);

        // Second visit should be blocked
        $response = $this->get('/'.$link->short_code);
        $response->assertStatus(403);
        $response->assertViewIs('link.click-limit-exceeded');

        // Click count should remain at 10
        $this->assertEquals(10, $link->fresh()->click_count);
    }

    public function test_job_increments_click_count_when_flag_is_set()
    {
        // Create a link
        $link = Link::factory()->create([
            'click_count' => 5,
        ]);

        // Dispatch job with increment flag
        $job = new LogClickJob([
            'link_id' => $link->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'referer' => null,
            'clicked_at' => now(),
            'increment_click_count' => true,
        ]);

        $job->handle();

        // Click count should be incremented
        $this->assertEquals(6, $link->fresh()->click_count);

        // Click record should be created
        $this->assertDatabaseHas('clicks', [
            'link_id' => $link->id,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_job_does_not_increment_when_flag_is_false()
    {
        // Create a link
        $link = Link::factory()->create([
            'click_count' => 5,
        ]);

        // Dispatch job without increment flag
        $job = new LogClickJob([
            'link_id' => $link->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'referer' => null,
            'clicked_at' => now(),
            'increment_click_count' => false,
        ]);

        $job->handle();

        // Click count should NOT be incremented
        $this->assertEquals(5, $link->fresh()->click_count);

        // Click record should still be created
        $this->assertDatabaseHas('clicks', [
            'link_id' => $link->id,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_async_tracking_disabled_increments_synchronously()
    {
        Queue::fake();

        // Disable async tracking
        config(['shortener.analytics.async_tracking' => false]);

        // Create a link without click limit
        $link = Link::factory()->create([
            'short_code' => 'sync123',
            'click_count' => 5,
            'click_limit' => null,
        ]);

        // Visit the link
        $response = $this->get('/'.$link->short_code);

        // Should redirect successfully
        $response->assertRedirect($link->original_url);

        // Click count SHOULD be incremented (even without click limit)
        $this->assertEquals(6, $link->fresh()->click_count);

        // No job should be dispatched
        Queue::assertNotPushed(LogClickJob::class);
    }

    /**
     * Helper to extract job data using reflection
     */
    private function getJobData($job)
    {
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('clickData');
        $property->setAccessible(true);

        return $property->getValue($job);
    }
}
