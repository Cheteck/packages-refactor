<?php

namespace IJIDeals\Location\Database\factories;

use IJIDeals\Location\Models\CityTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Location\Models\CityTranslation>
 */
class CityTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CityTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locale = $this->faker->randomElement(['en', 'fr', 'es']);

        return [
            'name' => $this->faker->city().' ('.strtoupper($locale).')',
            'description' => $this->faker->optional()->sentence,
        ];
    }
}
