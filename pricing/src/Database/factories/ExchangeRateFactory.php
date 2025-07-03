<?php

namespace IJIDeals\Pricing\Database\factories;

use IJIDeals\Pricing\Models\Currency;
use IJIDeals\Pricing\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Pricing\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency_id' => Currency::factory(),
            'to_currency_id' => Currency::factory(),
            'rate' => $this->faker->randomFloat(6, 0.5, 1.5),
            'fetched_at' => now(),
        ];
    }
}
