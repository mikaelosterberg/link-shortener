<?php

namespace Database\Factories;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Click>
 */
class ClickFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Click::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'referer' => $this->faker->optional()->url(),
            'country' => $this->faker->optional()->country(),
            'city' => $this->faker->optional()->city(),
            'clicked_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'utm_source' => $this->faker->optional()->randomElement(['google', 'facebook', 'twitter', 'email', 'newsletter']),
            'utm_medium' => $this->faker->optional()->randomElement(['cpc', 'social', 'email', 'referral', 'organic']),
            'utm_campaign' => $this->faker->optional()->words(2, true),
            'utm_term' => $this->faker->optional()->words(1, true),
            'utm_content' => $this->faker->optional()->words(2, true),
            'ab_test_variant_id' => null, // Can be set explicitly in tests
        ];
    }

    /**
     * Indicate that the click has UTM parameters.
     */
    public function withUtm(): static
    {
        return $this->state(fn (array $attributes) => [
            'utm_source' => $this->faker->randomElement(['google', 'facebook', 'twitter', 'email', 'newsletter']),
            'utm_medium' => $this->faker->randomElement(['cpc', 'social', 'email', 'referral', 'organic']),
            'utm_campaign' => $this->faker->words(2, true),
            'utm_term' => $this->faker->optional()->words(1, true),
            'utm_content' => $this->faker->optional()->words(2, true),
        ]);
    }

    /**
     * Indicate that the click has geographic data.
     */
    public function withGeography(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
        ]);
    }

    /**
     * Indicate that the click is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the click is for an A/B test variant.
     */
    public function forVariant($variantId): static
    {
        return $this->state(fn (array $attributes) => [
            'ab_test_variant_id' => $variantId,
        ]);
    }
}