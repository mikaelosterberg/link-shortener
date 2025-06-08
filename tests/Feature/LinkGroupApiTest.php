<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkGroupApiTest extends TestCase
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
            'permissions' => ['groups:create', 'groups:read', 'groups:update', 'groups:delete'],
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_list_link_groups()
    {
        LinkGroup::factory()->count(3)->create();

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->getJson('/api/groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'color', 'is_default'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_list_link_groups_simple_format()
    {
        LinkGroup::factory()->count(3)->create();

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->getJson('/api/groups?simple=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'is_default'],
                ],
            ])
            ->assertJsonMissing(['meta']);
    }

    public function test_can_create_link_group()
    {
        $data = [
            'name' => 'Marketing Links',
            'description' => 'Links for marketing campaigns',
            'color' => '#FF0000',
        ];

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/groups', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Link group created successfully',
                'data' => [
                    'name' => 'Marketing Links',
                    'description' => 'Links for marketing campaigns',
                    'color' => '#FF0000',
                ],
            ]);

        $this->assertDatabaseHas('link_groups', [
            'name' => 'Marketing Links',
            'is_default' => false,
        ]);
    }

    public function test_can_create_default_link_group()
    {
        $data = [
            'name' => 'Default Group',
            'color' => '#00FF00',
            'is_default' => true,
        ];

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/groups', $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Default Group',
                    'is_default' => true,
                ],
            ]);

        $this->assertDatabaseHas('link_groups', [
            'name' => 'Default Group',
            'is_default' => true,
        ]);
    }

    public function test_setting_new_default_unsets_previous_default()
    {
        // Create first default group
        $firstGroup = LinkGroup::create([
            'name' => 'First Group',
            'color' => '#FF0000',
            'is_default' => true,
        ]);

        // Create second group and set as default
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/groups', [
            'name' => 'Second Group',
            'color' => '#00FF00',
            'is_default' => true,
        ]);

        $response->assertStatus(201);

        // First group should no longer be default
        $firstGroup->refresh();
        $this->assertFalse($firstGroup->is_default);

        // Second group should be default
        $this->assertDatabaseHas('link_groups', [
            'name' => 'Second Group',
            'is_default' => true,
        ]);
    }

    public function test_can_update_link_group()
    {
        $group = LinkGroup::factory()->create();

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->putJson("/api/groups/{$group->id}", [
            'name' => 'Updated Name',
            'color' => '#0000FF',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Link group updated successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => 'Updated Name',
                    'color' => '#0000FF',
                ],
            ]);
    }

    public function test_cannot_delete_group_with_links()
    {
        $group = LinkGroup::factory()->hasLinks(2)->create();

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Cannot delete group with existing links',
            ]);
    }

    public function test_can_delete_empty_group()
    {
        $group = LinkGroup::factory()->create();

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Link group deleted successfully',
            ]);

        $this->assertDatabaseMissing('link_groups', ['id' => $group->id]);
    }

    public function test_requires_permissions_for_operations()
    {
        // Create API key with limited permissions
        $limitedKey = ApiKey::create([
            'name' => 'Limited Key',
            'api_key' => 'limited_key_123',
            'key_hash' => hash('sha256', 'limited_key_123'),
            'permissions' => ['groups:read'], // Only read permission
            'created_by' => $this->user->id,
        ]);

        // Try to create a group (should fail)
        $response = $this->withHeaders([
            'X-API-Key' => 'limited_key_123',
        ])->postJson('/api/groups', [
            'name' => 'Test Group',
            'color' => '#FF0000',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions',
            ]);
    }

    public function test_validation_errors()
    {
        // Missing required fields
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/groups', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Invalid color format
        $response = $this->withHeaders([
            'X-API-Key' => 'test_key_123',
        ])->postJson('/api/groups', [
            'name' => 'Test',
            'color' => 'invalid-color',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }
}
