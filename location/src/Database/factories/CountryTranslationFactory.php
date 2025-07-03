<?php

namespace IJIDeals\Location\Database\factories;

use IJIDeals\Location\Models\CountryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Location\Models\CountryTranslation>
 */
class CountryTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CountryTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locale = $this->faker->randomElement(['en', 'fr', 'es']);

        return [
            // country_id and locale will be set by Astrotomic when using CountryFactory's hasTranslations
            'name' => $this->faker->country().' ('.strtoupper($locale).')',
        ];
    }
}
