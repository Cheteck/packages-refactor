<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Friendship;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FriendshipFactory extends Factory
{
    /**
     * @var class-string<\IJIDeals\Social\Models\Friendship>
     */
    protected $model = Friendship::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'friend_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'blocked']),
        ];
    }

    /**
     * Indicate that the friendship is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the friendship is accepted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
            ];
        });
    }

    /**
     * Indicate that the friendship is blocked.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function blocked()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'blocked',
            ];
        });
    }
}
