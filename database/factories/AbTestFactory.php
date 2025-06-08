<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbTest>
 */
class AbTestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * Indicate that the A/B test is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the A/B test has time boundaries.
     */
    public function withTimeBoundaries(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the A/B test is scheduled for the future.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
    }

    /**
     * Indicate that the A/B test has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);
    }
}
