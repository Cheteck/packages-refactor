<?php

namespace IJIDeals\Location\Database\factories;

use IJIDeals\Location\Models\RegionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Location\Models\RegionTranslation>
 */
class RegionTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RegionTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locale = $this->faker->randomElement(['en', 'fr', 'es']);

        return [
            // region_id and locale will be set by Astrotomic
            'name' => $this->faker->state().' ('.strtoupper($locale).')', // Faker provides state names
            'description' => $this->faker->optional()->sentence,
        ];
    }
}
