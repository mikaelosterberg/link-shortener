<?php

namespace Tests\Unit;

use App\Models\Link;
use App\Models\LinkNotification;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_notification_groups_relationship(): void
    {
        $user = User::factory()->create();
        $group = NotificationGroup::factory()->create();

        $user->notificationGroups()->attach($group, ['is_active' => 1]);

        $this->assertCount(1, $user->notificationGroups);
        $this->assertEquals($group->id, $user->notificationGroups->first()->id);
    }

    public function test_user_has_active_notification_groups_relationship(): void
    {
        $user = User::factory()->create();
        $activeGroup = NotificationGroup::factory()->create();
        $inactiveGroup = NotificationGroup::factory()->create();

        $user->notificationGroups()->attach($activeGroup, ['is_active' => 1]);
        $user->notificationGroups()->attach($inactiveGroup, ['is_active' => 0]);

        $this->assertCount(2, $user->notificationGroups);
        $this->assertCount(1, $user->activeNotificationGroups);
        $this->assertEquals($activeGroup->id, $user->activeNotificationGroups->first()->id);
    }

    public function test_link_has_notification_relationships(): void
    {
        $link = Link::factory()->create();
        $linkNotification = LinkNotification::factory()->create([
            'link_id' => $link->id,
        ]);

        $this->assertCount(1, $link->linkNotifications);
        $this->assertEquals($linkNotification->id, $link->linkNotifications->first()->id);
    }

    public function test_link_has_active_notifications_relationship(): void
    {
        $link = Link::factory()->create();
        $activeNotification = LinkNotification::factory()->create([
            'link_id' => $link->id,
            'is_active' => true,
        ]);
        $inactiveNotification = LinkNotification::factory()->create([
            'link_id' => $link->id,
            'is_active' => false,
        ]);

        $this->assertCount(2, $link->linkNotifications);
        $this->assertCount(1, $link->activeNotifications);
        $this->assertEquals($activeNotification->id, $link->activeNotifications->first()->id);
    }

    public function test_complete_notification_chain(): void
    {
        // Create the complete chain: User -> NotificationGroup -> LinkNotification -> Link
        $user1 = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $user2 = User::factory()->create(['name' => 'Developer', 'email' => 'dev@example.com']);

        $group = NotificationGroup::factory()->create(['name' => 'Tech Team']);

        // Add users to group
        $group->users()->attach($user1, ['is_active' => 1]);
        $group->users()->attach($user2, ['is_active' => 1]);

        // Create links
        $link1 = Link::factory()->create(['original_url' => 'https://example.com/api']);
        $link2 = Link::factory()->create(['original_url' => 'https://example.com/app']);

        // Create link notifications
        $linkNotification1 = LinkNotification::factory()->create([
            'link_id' => $link1->id,
            'notification_group_id' => $group->id,
        ]);

        $linkNotification2 = LinkNotification::factory()->create([
            'link_id' => $link2->id,
            'notification_group_id' => $group->id,
        ]);

        // Test the complete chain
        $this->assertCount(2, $group->activeUsers);
        $this->assertCount(2, $group->linkNotifications);

        // Test that we can access users through notification group from link
        $notificationGroup = $link1->linkNotifications->first()->notificationGroup;
        $this->assertEquals('Tech Team', $notificationGroup->name);
        $this->assertCount(2, $notificationGroup->activeUsers);

        // Test that users are in the group
        $userEmails = $notificationGroup->activeUsers->pluck('email')->toArray();
        $this->assertContains('admin@example.com', $userEmails);
        $this->assertContains('dev@example.com', $userEmails);
    }

    public function test_notification_group_get_all_targets_includes_users_and_channels(): void
    {
        $group = NotificationGroup::factory()->create();

        // Add users
        $user = User::factory()->create(['email' => 'test@example.com', 'name' => 'Test User']);
        $group->users()->attach($user, ['is_active' => 1]);

        // Add a Slack channel (creating directly to avoid factory circular dependency)
        $slackChannel = $group->channels()->create([
            'name' => 'Dev Team Slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/test'],
            'is_active' => true,
            'settings' => [],
        ]);

        $targets = $group->getAllTargets();

        $this->assertCount(2, $targets);

        // Check user target
        $emailTarget = collect($targets)->where('type', 'email')->first();
        $this->assertEquals('test@example.com', $emailTarget['target']);
        $this->assertEquals('Test User', $emailTarget['name']);

        // Check channel target
        $slackTarget = collect($targets)->where('type', 'slack')->first();
        $this->assertEquals(['webhook_url' => 'https://hooks.slack.com/test'], $slackTarget['target']);
        $this->assertEquals('Dev Team Slack', $slackTarget['name']);
    }
}
