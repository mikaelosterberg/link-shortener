<?php

namespace Tests\Unit;

use App\Models\Link;
use App\Models\LinkNotification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_notification_can_be_created(): void
    {
        $link = Link::factory()->create();
        $group = NotificationGroup::factory()->create();
        $type = NotificationType::factory()->create();

        $linkNotification = LinkNotification::create([
            'link_id' => $link->id,
            'notification_group_id' => $group->id,
            'notification_type_id' => $type->id,
            'is_active' => true,
            'settings' => ['custom' => 'value'],
        ]);

        $this->assertInstanceOf(LinkNotification::class, $linkNotification);
        $this->assertEquals($link->id, $linkNotification->link_id);
        $this->assertEquals($group->id, $linkNotification->notification_group_id);
        $this->assertEquals($type->id, $linkNotification->notification_type_id);
        $this->assertTrue($linkNotification->is_active);
    }

    public function test_link_notification_belongs_to_link(): void
    {
        $link = Link::factory()->create();
        $linkNotification = LinkNotification::factory()->create([
            'link_id' => $link->id,
        ]);

        $this->assertEquals($link->id, $linkNotification->link->id);
        $this->assertEquals($link->original_url, $linkNotification->link->original_url);
    }

    public function test_link_notification_belongs_to_notification_group(): void
    {
        $group = NotificationGroup::factory()->create();
        $linkNotification = LinkNotification::factory()->create([
            'notification_group_id' => $group->id,
        ]);

        $this->assertEquals($group->id, $linkNotification->notificationGroup->id);
        $this->assertEquals($group->name, $linkNotification->notificationGroup->name);
    }

    public function test_link_notification_belongs_to_notification_type(): void
    {
        $type = NotificationType::factory()->create();
        $linkNotification = LinkNotification::factory()->create([
            'notification_type_id' => $type->id,
        ]);

        $this->assertEquals($type->id, $linkNotification->notificationType->id);
        $this->assertEquals($type->name, $linkNotification->notificationType->name);
    }

    public function test_link_notification_can_get_effective_settings(): void
    {
        $typeDefaults = [
            'retry_attempts' => 3,
            'retry_delay' => 300,
            'escalation_delay' => [3600, 14400],
        ];

        $linkOverrides = [
            'retry_attempts' => 5, // Override default
            'custom_setting' => 'value', // Additional setting
        ];

        $type = NotificationType::factory()->create([
            'default_settings' => $typeDefaults,
        ]);

        $linkNotification = LinkNotification::factory()->create([
            'notification_type_id' => $type->id,
            'settings' => $linkOverrides,
        ]);

        $effectiveSettings = $linkNotification->getEffectiveSettings();

        $expected = [
            'retry_attempts' => 5, // Overridden value
            'retry_delay' => 300, // Default value
            'escalation_delay' => [3600, 14400], // Default value
            'custom_setting' => 'value', // Additional setting
        ];

        $this->assertEquals($expected, $effectiveSettings);
    }

    public function test_link_notification_effective_settings_handles_null_values(): void
    {
        $type = NotificationType::factory()->create([
            'default_settings' => null,
        ]);

        $linkNotification = LinkNotification::factory()->create([
            'notification_type_id' => $type->id,
            'settings' => null,
        ]);

        $effectiveSettings = $linkNotification->getEffectiveSettings();

        $this->assertEquals([], $effectiveSettings);
    }

    public function test_link_notification_scope_active(): void
    {
        $activeLinkNotification = LinkNotification::factory()->create(['is_active' => true]);
        $inactiveLinkNotification = LinkNotification::factory()->create(['is_active' => false]);

        $activeLinkNotifications = LinkNotification::active()->get();

        $this->assertCount(1, $activeLinkNotifications);
        $this->assertEquals($activeLinkNotification->id, $activeLinkNotifications->first()->id);
    }

    public function test_link_notification_settings_are_cast_to_array(): void
    {
        $settings = ['retry_count' => 3, 'timeout' => 30];

        $linkNotification = LinkNotification::factory()->create([
            'settings' => $settings,
        ]);

        $this->assertIsArray($linkNotification->settings);
        $this->assertEquals($settings, $linkNotification->settings);
    }

    public function test_link_notification_is_active_is_cast_to_boolean(): void
    {
        $linkNotification = LinkNotification::factory()->create([
            'is_active' => 1,
        ]);

        $this->assertIsBool($linkNotification->is_active);
        $this->assertTrue($linkNotification->is_active);
    }
}
