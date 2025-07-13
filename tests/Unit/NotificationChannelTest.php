<?php

namespace Tests\Unit;

use App\Models\NotificationChannel;
use App\Models\NotificationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_channel_can_be_created(): void
    {
        $group = NotificationGroup::factory()->create();

        $channel = NotificationChannel::create([
            'notification_group_id' => $group->id,
            'name' => 'Test Webhook',
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/webhook'],
            'is_active' => true,
        ]);

        $this->assertInstanceOf(NotificationChannel::class, $channel);
        $this->assertEquals('Test Webhook', $channel->name);
        $this->assertEquals('webhook', $channel->type);
        $this->assertEquals(['url' => 'https://example.com/webhook'], $channel->config);
        $this->assertTrue($channel->is_active);
    }

    public function test_notification_channel_belongs_to_group(): void
    {
        $group = NotificationGroup::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'notification_group_id' => $group->id,
        ]);

        $this->assertEquals($group->id, $channel->notificationGroup->id);
    }

    public function test_notification_channel_has_available_types(): void
    {
        $types = NotificationChannel::getAvailableTypes();

        $expectedTypes = [
            'email' => 'Email',
            'webhook' => 'Webhook',
            'slack' => 'Slack',
            'discord' => 'Discord',
            'teams' => 'Microsoft Teams',
        ];

        $this->assertEquals($expectedTypes, $types);
    }

    public function test_notification_channel_has_config_schema(): void
    {
        $webhookSchema = NotificationChannel::getConfigSchema('webhook');
        $slackSchema = NotificationChannel::getConfigSchema('slack');
        $emailSchema = NotificationChannel::getConfigSchema('email');

        $this->assertArrayHasKey('url', $webhookSchema);
        $this->assertArrayHasKey('webhook_url', $slackSchema);
        $this->assertArrayHasKey('email', $emailSchema);
    }

    public function test_notification_channel_validates_webhook_config(): void
    {
        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/webhook', 'method' => 'POST'],
        ]);

        $this->assertTrue($channel->validateConfig());
    }

    public function test_notification_channel_fails_validation_with_missing_config(): void
    {
        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'config' => [], // Missing required URL
        ]);

        $this->assertFalse($channel->validateConfig());
    }

    public function test_notification_channel_validates_slack_config(): void
    {
        $validChannel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/test'],
        ]);

        $invalidChannel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['channel' => '#general'], // Missing required webhook_url
        ]);

        $this->assertTrue($validChannel->validateConfig());
        $this->assertFalse($invalidChannel->validateConfig());
    }

    public function test_notification_channel_config_display_attribute(): void
    {
        $webhookChannel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/webhook'],
        ]);

        $slackChannel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/test', 'channel' => '#general'],
        ]);

        $emailChannel = NotificationChannel::factory()->create([
            'type' => 'email',
            'config' => ['email' => 'test@example.com'],
        ]);

        $this->assertEquals('https://example.com/webhook', $webhookChannel->config_display);
        $this->assertEquals('#general', $slackChannel->config_display);
        $this->assertEquals('test@example.com', $emailChannel->config_display);
    }

    public function test_notification_channel_scope_active(): void
    {
        $activeChannel = NotificationChannel::factory()->create(['is_active' => true]);
        $inactiveChannel = NotificationChannel::factory()->create(['is_active' => false]);

        $activeChannels = NotificationChannel::active()->get();

        $this->assertCount(1, $activeChannels);
        $this->assertEquals($activeChannel->id, $activeChannels->first()->id);
    }

    public function test_notification_channel_config_is_cast_to_array(): void
    {
        $config = ['url' => 'https://example.com', 'method' => 'POST'];

        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'config' => $config,
        ]);

        $this->assertIsArray($channel->config);
        $this->assertEquals($config, $channel->config);
    }

    public function test_notification_channel_settings_are_cast_to_array(): void
    {
        $settings = ['retry_count' => 3, 'timeout' => 30];

        $channel = NotificationChannel::factory()->create([
            'settings' => $settings,
        ]);

        $this->assertIsArray($channel->settings);
        $this->assertEquals($settings, $channel->settings);
    }
}
