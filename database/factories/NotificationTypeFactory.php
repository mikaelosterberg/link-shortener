<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationType>
 */
class NotificationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueName = $this->faker->unique()->word().'_'.$this->faker->randomNumber(3);

        return [
            'name' => $uniqueName,
            'display_name' => ucwords(str_replace('_', ' ', $uniqueName)),
            'description' => $this->faker->sentence(),
            'default_groups' => [],
            'notify_link_owner' => $this->faker->boolean(),
            'apply_to_link_groups' => null,
            'exclude_blocked_links' => $this->faker->boolean(),
            'is_active' => true,
            'default_settings' => [],
        ];
    }
}
