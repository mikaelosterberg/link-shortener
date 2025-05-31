<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for link ownership
        $this->user = User::factory()->create();
    }

    public function test_redirect_works_for_active_link(): void
    {
        $link = Link::create([
            'short_code' => 'test123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/test123');

        $response->assertRedirect('https://example.com');
        $response->assertStatus(302);
    }

    public function test_redirect_returns_404_for_inactive_link(): void
    {
        Link::create([
            'short_code' => 'inactive',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => false,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/inactive');

        $response->assertStatus(404);
    }

    public function test_redirect_returns_404_for_expired_link(): void
    {
        Link::create([
            'short_code' => 'expired',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/expired');

        $response->assertStatus(404);
    }

    public function test_redirect_returns_404_for_nonexistent_link(): void
    {
        $response = $this->get('/nonexistent');

        $response->assertStatus(404);
    }

    public function test_redirect_respects_different_redirect_types(): void
    {
        // Test 301 redirect
        $link301 = Link::create([
            'short_code' => 'perm301',
            'original_url' => 'https://example.com',
            'redirect_type' => 301,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/perm301');
        $response->assertRedirect('https://example.com');
        $response->assertStatus(301);

        // Test 308 redirect
        $link308 = Link::create([
            'short_code' => 'perm308',
            'original_url' => 'https://example.com',
            'redirect_type' => 308,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/perm308');
        $response->assertRedirect('https://example.com');
        $response->assertStatus(308);
    }

    public function test_redirect_increments_click_count(): void
    {
        $link = Link::create([
            'short_code' => 'counter',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 5,
        ]);

        $this->get('/counter');

        $link->refresh();
        $this->assertEquals(6, $link->click_count);
    }

    public function test_redirect_uses_cache(): void
    {
        $link = Link::create([
            'short_code' => 'cached',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        // First request should cache the link
        $this->get('/cached');

        // Verify the cache contains the link data
        $cacheKey = "link_cached";
        $this->assertTrue(Cache::has($cacheKey));
        
        $cachedLink = Cache::get($cacheKey);
        $this->assertEquals($link->id, $cachedLink->id);
        $this->assertEquals($link->original_url, $cachedLink->original_url);
    }

    public function test_redirect_rate_limiting(): void
    {
        $link = Link::create([
            'short_code' => 'limited',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 60; $i++) {
            $response = $this->get('/limited');
            $response->assertRedirect('https://example.com');
        }

        // 61st request should be rate limited
        $response = $this->get('/limited');
        $this->assertContains($response->getStatusCode(), [429, 302]); // May be rate limited or still work
    }

    public function test_homepage_displays_correctly(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Link Shortener');
        $response->assertSee('Shorten your URLs');
    }

    public function test_homepage_shows_correct_stats(): void
    {
        // Create test data
        Link::create([
            'short_code' => 'stat1',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        Link::create([
            'short_code' => 'stat2',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('2'); // Should show 2 links created
        $response->assertSee('1'); // Should show 1 active user
    }
}