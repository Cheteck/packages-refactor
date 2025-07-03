<?php

namespace IJIDeals\Social\Database\factories;

use IJIDeals\Social\Models\Friendship;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Social\Models\Friendship>
 */
class FriendshipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Friendship::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'friend_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'blocked']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'accepted']);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'blocked']);
    }
}
