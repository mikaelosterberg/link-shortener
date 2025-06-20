<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeoRule>
 */
class GeoRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $matchTypes = ['country', 'continent', 'region'];
        $matchType = $this->faker->randomElement($matchTypes);

        $matchValues = match ($matchType) {
            'country' => $this->faker->randomElements(['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'JP'], $this->faker->numberBetween(1, 3)),
            'continent' => $this->faker->randomElements(['NA', 'EU', 'AS', 'OC', 'SA', 'AF'], $this->faker->numberBetween(1, 2)),
            'region' => $this->faker->randomElements(['north_america', 'europe', 'asia_pacific', 'middle_east', 'latin_america'], $this->faker->numberBetween(1, 2)),
        };

        return [
            'link_id' => Link::factory(),
            'match_type' => $matchType,
            'match_values' => $matchValues,
            'redirect_url' => $this->faker->url(),
            'priority' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Create a geo rule for specific countries.
     */
    public function forCountries(array $countries): static
    {
        return $this->state(fn (array $attributes) => [
            'match_type' => 'country',
            'match_values' => $countries,
        ]);
    }

    /**
     * Create a geo rule for specific continents.
     */
    public function forContinents(array $continents): static
    {
        return $this->state(fn (array $attributes) => [
            'match_type' => 'continent',
            'match_values' => $continents,
        ]);
    }

    /**
     * Create a geo rule for specific regions.
     */
    public function forRegions(array $regions): static
    {
        return $this->state(fn (array $attributes) => [
            'match_type' => 'region',
            'match_values' => $regions,
        ]);
    }

    /**
     * Create an active geo rule.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive geo rule.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
