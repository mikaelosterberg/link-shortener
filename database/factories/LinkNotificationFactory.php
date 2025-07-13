<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkNotification>
 */
class LinkNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => \App\Models\Link::factory(),
            'notification_group_id' => \App\Models\NotificationGroup::factory(),
            'notification_type_id' => \App\Models\NotificationType::factory(),
            'is_active' => true,
            'settings' => [],
        ];
    }
}
