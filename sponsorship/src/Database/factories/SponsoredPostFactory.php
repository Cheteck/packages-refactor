<?php

namespace IJIDeals\Sponsorship\Database\factories;

use IJIDeals\Social\Models\Post;
use IJIDeals\Sponsorship\Models\SponsoredPost;
use IJIDeals\UserManagement\Models\User; // Assuming User model path
use Illuminate\Database\Eloquent\Factories\Factory; // Assuming Post model path

class SponsoredPostFactory extends Factory
{
    protected $model = SponsoredPost::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 week', '+1 week');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s').' +1 month');

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'budget' => $this->faker->randomFloat(2, 10, 1000),
            'cost_per_impression' => $this->faker->randomFloat(4, 0.001, 0.1),
            'cost_per_click' => $this->faker->randomFloat(4, 0.05, 2),
            'spent_amount' => 0,
            'targeting' => [
                'age_min' => $this->faker->numberBetween(18, 25),
                'age_max' => $this->faker->numberBetween(35, 65),
                'interests' => $this->faker->randomElements(['technology', 'sports', 'music', 'travel'], $this->faker->numberBetween(1, 3)),
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['pending', 'active', 'paused', 'completed', 'cancelled']),
            'impressions' => 0,
            'clicks' => 0,
        ];
    }

    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonth(),
            ];
        });
    }

    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'spent_amount' => $attributes['budget'], // Assuming budget is fully spent
                'end_date' => now()->subDay(),
            ];
        });
    }

    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }
}
