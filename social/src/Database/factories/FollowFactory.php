<?php

namespace IJIDeals\Social\Database\factories;

use IJIDeals\Social\Models\Follow;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Social\Models\Follow>
 */
class FollowFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Follow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Note: followable_id and followable_type should typically be set
        // when calling the factory, as they depend on the specific entity being followed.
        return [
            'user_id' => User::factory(),
            // Example for followable (replace with actual model when using):
            // 'followable_id' => User::factory(),
            // 'followable_type' => User::class,
        ];
    }

    /**
     * Indicate that the follow is for a specific model.
     */
    public function forFollowable(\Illuminate\Database\Eloquent\Model $followable): static
    {
        return $this->state(fn (array $attributes) => [
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->getKey(),
        ]);
    }
}
