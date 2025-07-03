<?php

namespace IJIDeals\Pricing\Database\factories;

use IJIDeals\Pricing\Models\PricingTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Pricing\Models\PricingTier>
 */
class PricingTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PricingTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word.' Tier';

        return [
            'name' => $name,
            'key' => strtolower(str_replace(' ', '_', $name)),
            'description' => $this->faker->sentence,
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
