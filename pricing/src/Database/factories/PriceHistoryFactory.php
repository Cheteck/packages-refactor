<?php

namespace IJIDeals\Pricing\Database\factories;

use IJIDeals\Pricing\Models\Price;
use IJIDeals\Pricing\Models\PriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Pricing\Models\PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PriceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_id' => Price::factory(),
            'old_amount' => $this->faker->randomFloat(4, 5, 90),
            'new_amount' => $this->faker->randomFloat(4, 10, 100),
            'changed_at' => now(),
            // Assuming user_id can be null or you have a User factory accessible
            // 'user_id' => User::factory(),
        ];
    }
}
