<?php

namespace Tests\Unit;

use App\Models\NotificationChannel;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_group_can_be_created(): void
    {
        $group = NotificationGroup::create([
            'name' => 'Test Group',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(NotificationGroup::class, $group);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('Test description', $group->description);
        $this->assertTrue($group->is_active);
    }

    public function test_notification_group_has_users_relationship(): void
    {
        $group = NotificationGroup::factory()->create();
        $user = User::factory()->create();

        $group->users()->attach($user, ['is_active' => true]);

        $this->assertCount(1, $group->users);
        $this->assertEquals($user->id, $group->users->first()->id);
        $this->assertEquals(1, $group->users->first()->pivot->is_active);
    }

    public function test_notification_group_has_active_users_relationship(): void
    {
        $group = NotificationGroup::factory()->create();
        $activeUser = User::factory()->create();
        $inactiveUser = User::factory()->create();

        $group->users()->attach($activeUser, ['is_active' => 1]);
        $group->users()->attach($inactiveUser, ['is_active' => 0]);

        $this->assertCount(2, $group->users);
        $this->assertCount(1, $group->activeUsers);
        $this->assertEquals($activeUser->id, $group->activeUsers->first()->id);
    }

    public function test_notification_group_has_channels_relationship(): void
    {
        $group = NotificationGroup::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
        ]);

        $this->assertCount(1, $group->channels);
        $this->assertEquals($channel->id, $group->channels->first()->id);
    }

    public function test_notification_group_has_active_channels_relationship(): void
    {
        $group = NotificationGroup::factory()->create();
        $activeChannel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
            'is_active' => true,
        ]);
        $inactiveChannel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
            'is_active' => false,
        ]);

        $this->assertCount(2, $group->channels);
        $this->assertCount(1, $group->activeChannels);
        $this->assertEquals($activeChannel->id, $group->activeChannels->first()->id);
    }

    public function test_notification_group_can_get_all_targets(): void
    {
        $group = NotificationGroup::factory()->create();

        // Add users
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $group->users()->attach([$user1->id => ['is_active' => 1], $user2->id => ['is_active' => 1]]);

        // Add channels
        $slackChannel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
            'name' => 'Dev Team Slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/test'],
            'is_active' => true,
        ]);

        $webhookChannel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
            'name' => 'API Webhook',
            'type' => 'webhook',
            'config' => ['url' => 'https://api.example.com/webhook'],
            'is_active' => true,
        ]);

        $targets = $group->getAllTargets();

        $this->assertCount(4, $targets); // 2 users + 2 channels

        // Check user targets
        $userTargets = collect($targets)->where('type', 'email');
        $this->assertCount(2, $userTargets);
        $this->assertTrue($userTargets->contains('target', 'john@example.com'));
        $this->assertTrue($userTargets->contains('target', 'jane@example.com'));

        // Check channel targets
        $channelTargets = collect($targets)->whereIn('type', ['slack', 'webhook']);
        $this->assertCount(2, $channelTargets);
    }

    public function test_notification_group_scope_active(): void
    {
        $activeGroup = NotificationGroup::factory()->create(['is_active' => true]);
        $inactiveGroup = NotificationGroup::factory()->create(['is_active' => false]);

        $activeGroups = NotificationGroup::active()->get();

        $this->assertCount(1, $activeGroups);
        $this->assertEquals($activeGroup->id, $activeGroups->first()->id);
    }

    public function test_notification_group_settings_are_cast_to_array(): void
    {
        $settings = ['retry_delay' => 300, 'max_retries' => 3];

        $group = NotificationGroup::create([
            'name' => 'Test Group',
            'description' => 'Test description',
            'is_active' => true,
            'settings' => $settings,
        ]);

        $this->assertIsArray($group->settings);
        $this->assertEquals($settings, $group->settings);
    }
}
