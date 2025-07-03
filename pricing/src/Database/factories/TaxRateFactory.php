<?php

namespace IJIDeals\Pricing\Database\factories;

use IJIDeals\Pricing\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Pricing\Models\TaxRate>
 */
class TaxRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Tax',
            'rate_percentage' => $this->faker->randomElement([5.00, 10.00, 15.00, 20.00]),
            'is_active' => $this->faker->boolean(90),
            'priority' => $this->faker->numberBetween(0, 5),
            'country_code' => $this->faker->boolean(70) ? $this->faker->countryCode() : null,
            'region' => $this->faker->boolean(50) ? $this->faker->state() : null,
            'city' => $this->faker->boolean(30) ? $this->faker->city() : null,
            'zip_code' => $this->faker->boolean(40) ? $this->faker->postcode() : null,
            'description' => $this->faker->sentence,
        ];
    }
}
