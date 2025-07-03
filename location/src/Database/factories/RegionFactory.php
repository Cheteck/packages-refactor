<?php

namespace IJIDeals\Location\Database\factories;

use IJIDeals\Location\Models\Country;
use IJIDeals\Location\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Location\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Region::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 'name' and 'description' are translatable.
        // 'slug' will be generated from 'name' by Sluggable.
        return [
            'country_id' => Country::factory(),
            'code' => $this->faker->optional(0.7)->stateAbbr(), // e.g., CA, TX, NY
            'status' => true,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Region $region) {
            if ($region->translations()->where('locale', config('app.locale', 'en'))->doesntExist()) {
                $defaultLocale = config('app.locale', 'en');
                $region->translations()->create([
                    'locale' => $defaultLocale,
                    'name' => $this->faker->state().' ('.$defaultLocale.')', // Faker state name
                    'description' => $this->faker->optional()->sentence,
                ]);
                $region->refresh();
            }
        });
    }
}
