<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->apiKey = ApiKey::create([
            'name' => 'Test API Key',
            'api_key' => 'test_key_123',
            'key_hash' => hash('sha256', 'test_key_123'),
            'permissions' => ['links:create', 'links:read'],
            'created_by' => $this->user->id,
        ]);
    }

    public function test_link_uses_default_group_when_no_group_specified()
    {
        // Create a default group
        $defaultGroup = LinkGroup::create([
            'name' => 'Default Group',
            'color' => '#FF0000',
            'is_default' => true,
        ]);

        // Create link without specifying group_id
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/links', [
            'original_url' => 'https://example.com',
        ]);

        $response->assertStatus(201);
        
        // Check that the link was assigned to the default group
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com',
            'group_id' => $defaultGroup->id,
        ]);
    }

    public function test_link_respects_specified_group_over_default()
    {
        // Create a default group
        $defaultGroup = LinkGroup::create([
            'name' => 'Default Group',
            'color' => '#FF0000',
            'is_default' => true,
        ]);

        // Create another group
        $otherGroup = LinkGroup::create([
            'name' => 'Other Group',
            'color' => '#00FF00',
            'is_default' => false,
        ]);

        // Create link with specific group_id
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/links', [
            'original_url' => 'https://example.com',
            'group_id' => $otherGroup->id,
        ]);

        $response->assertStatus(201);
        
        // Check that the link was assigned to the specified group, not default
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com',
            'group_id' => $otherGroup->id,
        ]);
    }

    public function test_link_has_no_group_when_no_default_exists()
    {
        // Create link without any default group
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/links', [
            'original_url' => 'https://example.com',
        ]);

        $response->assertStatus(201);
        
        // Check that the link has no group
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com',
            'group_id' => null,
        ]);
    }

    public function test_only_one_default_group_exists()
    {
        // Create first default group
        $firstGroup = LinkGroup::create([
            'name' => 'First Group',
            'color' => '#FF0000',
            'is_default' => true,
        ]);

        // Create second group and set as default
        $secondGroup = LinkGroup::create([
            'name' => 'Second Group',
            'color' => '#00FF00',
            'is_default' => false,
        ]);
        
        $secondGroup->setAsDefault();

        // Refresh first group and check it's no longer default
        $firstGroup->refresh();
        $this->assertFalse($firstGroup->is_default);
        $this->assertTrue($secondGroup->is_default);

        // Check database has only one default
        $this->assertEquals(1, LinkGroup::where('is_default', true)->count());
    }

    public function test_get_default_returns_correct_group()
    {
        // No default initially
        $this->assertNull(LinkGroup::getDefault());

        // Create default group
        $defaultGroup = LinkGroup::create([
            'name' => 'Default Group',
            'color' => '#FF0000',
            'is_default' => true,
        ]);

        // Should return the default group
        $found = LinkGroup::getDefault();
        $this->assertNotNull($found);
        $this->assertEquals($defaultGroup->id, $found->id);
    }
}