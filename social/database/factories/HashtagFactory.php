<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HashtagFactory extends Factory
{
    /**
     * @var class-string<Hashtag>
     */
    protected $model = Hashtag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->unique()->word;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'post_count' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
