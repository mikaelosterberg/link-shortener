<?php

namespace Tests\Unit;

use App\Models\Click;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->group = LinkGroup::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'color' => '#3B82F6',
        ]);
    }

    public function test_link_can_be_created(): void
    {
        $link = Link::create([
            'short_code' => 'test123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $this->assertDatabaseHas('links', [
            'short_code' => 'test123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
        ]);
    }

    public function test_link_belongs_to_user(): void
    {
        $link = Link::create([
            'short_code' => 'user123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $this->assertEquals($this->user->id, $link->creator->id);
        $this->assertEquals($this->user->name, $link->creator->name);
    }

    public function test_link_belongs_to_group(): void
    {
        $link = Link::create([
            'short_code' => 'group123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'group_id' => $this->group->id,
            'click_count' => 0,
        ]);

        $this->assertEquals($this->group->id, $link->group->id);
        $this->assertEquals($this->group->name, $link->group->name);
    }

    public function test_link_has_many_clicks(): void
    {
        $link = Link::create([
            'short_code' => 'clicks123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        // Create some clicks
        Click::create([
            'link_id' => $link->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'referer' => 'https://test.com',
            'clicked_at' => now(),
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Another Browser',
            'referer' => 'https://test2.com',
            'clicked_at' => now(),
        ]);

        $this->assertCount(2, $link->clicks);
    }

    public function test_link_expiration_check(): void
    {
        $expiredLink = Link::create([
            'short_code' => 'expired123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $activeLink = Link::create([
            'short_code' => 'active123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'expires_at' => now()->addDay(),
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $neverExpiresLink = Link::create([
            'short_code' => 'never123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'expires_at' => null,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $this->assertTrue($expiredLink->isExpired());
        $this->assertFalse($activeLink->isExpired());
        $this->assertFalse($neverExpiresLink->isExpired());
    }

    public function test_link_scope_active(): void
    {
        $activeLink = Link::create([
            'short_code' => 'active456',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $inactiveLink = Link::create([
            'short_code' => 'inactive456',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => false,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $activeLinks = Link::where('is_active', true)->get();
        $inactiveLinks = Link::where('is_active', false)->get();

        $this->assertCount(1, $activeLinks);
        $this->assertCount(1, $inactiveLinks);
        $this->assertEquals('active456', $activeLinks->first()->short_code);
        $this->assertEquals('inactive456', $inactiveLinks->first()->short_code);
    }

    public function test_link_custom_slug_handling(): void
    {
        $linkWithCustomSlug = Link::create([
            'short_code' => 'my-custom-url',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'custom_slug' => 'my-custom-url',
            'click_count' => 0,
        ]);

        $this->assertEquals('my-custom-url', $linkWithCustomSlug->short_code);
        $this->assertEquals('my-custom-url', $linkWithCustomSlug->custom_slug);
    }

    public function test_link_increment_click_count(): void
    {
        $link = Link::create([
            'short_code' => 'counter123',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 5,
        ]);

        // Simulate incrementing click count
        $link->increment('click_count');
        $link->refresh();

        $this->assertEquals(6, $link->click_count);
    }
}