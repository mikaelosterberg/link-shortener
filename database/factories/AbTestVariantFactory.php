<?php

namespace Database\Factories;

use App\Models\AbTest;
use App\Models\AbTestVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbTestVariant>
 */
class AbTestVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ab_test_id' => AbTest::factory(),
            'name' => fake()->word(),
            'url' => fake()->url(),
            'weight' => 50,
            'click_count' => 0,
            'conversion_count' => 0,
        ];
    }
    
    /**
     * Indicate that the variant has performance data.
     */
    public function withPerformanceData(): static
    {
        return $this->state(fn (array $attributes) => [
            'click_count' => fake()->numberBetween(50, 500),
            'conversion_count' => fake()->numberBetween(5, 50),
        ]);
    }
}