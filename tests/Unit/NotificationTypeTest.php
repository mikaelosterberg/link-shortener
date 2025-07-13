<?php

namespace Tests\Unit;

use App\Models\NotificationGroup;
use App\Models\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_type_can_be_created(): void
    {
        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Notifications when links go down',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(NotificationType::class, $type);
        $this->assertEquals('link_health', $type->name);
        $this->assertEquals('Link Health Alert', $type->display_name);
        $this->assertTrue($type->is_active);
    }

    public function test_notification_type_has_default_types(): void
    {
        $defaultTypes = NotificationType::getDefaultTypes();

        $this->assertIsArray($defaultTypes);
        $this->assertCount(3, $defaultTypes);

        // Check that default types contain expected types
        $typeNames = array_column($defaultTypes, 'name');
        $this->assertContains('link_health', $typeNames);
        $this->assertContains('system_alert', $typeNames);
        $this->assertContains('maintenance', $typeNames);
    }

    public function test_notification_type_can_get_default_groups(): void
    {
        $group1 = NotificationGroup::factory()->create();
        $group2 = NotificationGroup::factory()->create();

        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Test description',
            'default_groups' => [$group1->id, $group2->id],
            'is_active' => true,
        ]);

        $defaultGroups = $type->getDefaultGroups();

        $this->assertCount(2, $defaultGroups);
        $this->assertTrue($defaultGroups->contains('id', $group1->id));
        $this->assertTrue($defaultGroups->contains('id', $group2->id));
    }

    public function test_notification_type_returns_empty_collection_for_no_groups(): void
    {
        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Test description',
            'default_groups' => [],
            'is_active' => true,
        ]);

        $defaultGroups = $type->getDefaultGroups();

        $this->assertCount(0, $defaultGroups);
    }

    public function test_notification_type_scope_active(): void
    {
        $activeType = NotificationType::factory()->create(['is_active' => true]);
        $inactiveType = NotificationType::factory()->create(['is_active' => false]);

        $activeTypes = NotificationType::active()->get();

        $this->assertCount(1, $activeTypes);
        $this->assertEquals($activeType->id, $activeTypes->first()->id);
    }

    public function test_notification_type_boolean_fields_are_cast(): void
    {
        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Test description',
            'is_active' => 1,
            'notify_link_owner' => 1,
            'exclude_blocked_links' => 1,
        ]);

        $this->assertIsBool($type->is_active);
        $this->assertIsBool($type->notify_link_owner);
        $this->assertIsBool($type->exclude_blocked_links);
        $this->assertTrue($type->is_active);
        $this->assertTrue($type->notify_link_owner);
        $this->assertTrue($type->exclude_blocked_links);
    }

    public function test_notification_type_array_fields_are_cast(): void
    {
        $groups = [1, 2, 3];
        $settings = ['retry_delay' => 300, 'max_retries' => 3];
        $linkGroups = [4, 5];

        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Test description',
            'default_groups' => $groups,
            'default_settings' => $settings,
            'apply_to_link_groups' => $linkGroups,
            'is_active' => true,
        ]);

        $this->assertIsArray($type->default_groups);
        $this->assertIsArray($type->default_settings);
        $this->assertIsArray($type->apply_to_link_groups);

        $this->assertEquals($groups, $type->default_groups);
        $this->assertEquals($settings, $type->default_settings);
        $this->assertEquals($linkGroups, $type->apply_to_link_groups);
    }

    public function test_notification_type_can_handle_null_groups(): void
    {
        $type = NotificationType::create([
            'name' => 'link_health',
            'display_name' => 'Link Health Alert',
            'description' => 'Test description',
            'default_groups' => null,
            'is_active' => true,
        ]);

        $defaultGroups = $type->getDefaultGroups();
        $this->assertCount(0, $defaultGroups);
    }
}
