<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'short_code' => $this->faker->unique()->lexify('??????'),
            'original_url' => $this->faker->url(),
            'redirect_type' => $this->faker->randomElement([301, 302, 307, 308]),
            'is_active' => true,
            'click_count' => 0,
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
