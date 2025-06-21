<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'user_id' => \App\Models\User::factory(),
            'layout_config' => null,
            'global_filters' => [
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->toDateString(),
            ],
            'schedule_config' => null,
            'is_template' => $this->faker->boolean(20), // 20% chance
            'visibility' => $this->faker->randomElement(['private', 'team', 'public']),
            'is_active' => true,
            'last_generated_at' => null,
        ];
    }
}
