<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['webhook', 'slack', 'discord', 'teams']);

        $config = match ($type) {
            'webhook' => [
                'url' => $this->faker->url(),
                'method' => 'POST',
            ],
            'slack' => [
                'webhook_url' => 'https://hooks.slack.com/test/'.$this->faker->uuid(),
                'channel' => '#'.$this->faker->word(),
            ],
            'discord' => [
                'webhook_url' => 'https://discord.com/api/webhooks/'.$this->faker->uuid(),
            ],
            'teams' => [
                'webhook_url' => 'https://outlook.office.com/webhook/'.$this->faker->uuid(),
            ],
        };

        return [
            'notification_group_id' => \App\Models\NotificationGroup::factory(),
            'name' => $this->faker->words(2, true),
            'type' => $type,
            'config' => $config,
            'is_active' => true,
            'settings' => [],
        ];
    }
}
