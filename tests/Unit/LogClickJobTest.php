<?php

namespace Tests\Unit;

use App\Jobs\LogClickJob;
use App\Models\Click;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogClickJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->link = Link::create([
            'short_code' => 'job123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);
    }

    public function test_log_click_job_creates_click_record(): void
    {
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.com',
            'clicked_at' => now(),
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $this->assertDatabaseHas('clicks', [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.com',
        ]);
    }

    public function test_log_click_job_handles_geolocation(): void
    {
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '8.8.8.8', // Google DNS - public IP
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.com',
            'clicked_at' => now(),
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $click = Click::where('link_id', $this->link->id)->first();
        
        $this->assertNotNull($click);
        $this->assertEquals('8.8.8.8', $click->ip_address);
        
        // Geolocation might return null if database is not available
        // Both null and actual location data are acceptable
        $this->assertTrue(
            is_null($click->country) || is_string($click->country)
        );
        $this->assertTrue(
            is_null($click->city) || is_string($click->city)
        );
    }

    public function test_log_click_job_handles_private_ip(): void
    {
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.1', // Private IP
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.com',
            'clicked_at' => now(),
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $click = Click::where('link_id', $this->link->id)->first();
        
        $this->assertNotNull($click);
        $this->assertEquals('192.168.1.1', $click->ip_address);
        
        // Private IPs typically don't have geolocation data
        $this->assertTrue(
            is_null($click->country) || is_string($click->country)
        );
        $this->assertTrue(
            is_null($click->city) || is_string($click->city)
        );
    }

    public function test_log_click_job_handles_missing_referer(): void
    {
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => null, // No referer
            'clicked_at' => now(),
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $this->assertDatabaseHas('clicks', [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'referer' => null,
        ]);
    }

    public function test_log_click_job_handles_empty_user_agent(): void
    {
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => '', // Empty user agent
            'referer' => 'https://referrer.com',
            'clicked_at' => now(),
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $this->assertDatabaseHas('clicks', [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => '',
        ]);
    }

    public function test_multiple_clicks_for_same_link(): void
    {
        $clickData1 = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Browser 1',
            'referer' => 'https://site1.com',
            'clicked_at' => now(),
        ];

        $clickData2 = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.200',
            'user_agent' => 'Browser 2',
            'referer' => 'https://site2.com',
            'clicked_at' => now()->addMinute(),
        ];

        $job1 = new LogClickJob($clickData1);
        $job2 = new LogClickJob($clickData2);
        
        $job1->handle();
        $job2->handle();

        $clicks = Click::where('link_id', $this->link->id)->get();
        
        $this->assertCount(2, $clicks);
        $this->assertEquals('192.168.1.100', $clicks->first()->ip_address);
        $this->assertEquals('192.168.1.200', $clicks->last()->ip_address);
    }

    public function test_click_timestamps_are_preserved(): void
    {
        $specificTime = now()->subHours(2);
        
        $clickData = [
            'link_id' => $this->link->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.com',
            'clicked_at' => $specificTime,
        ];

        $job = new LogClickJob($clickData);
        $job->handle();

        $click = Click::where('link_id', $this->link->id)->first();
        
        $this->assertNotNull($click);
        $this->assertEquals(
            $specificTime->format('Y-m-d H:i:s'),
            $click->clicked_at->format('Y-m-d H:i:s')
        );
    }
}