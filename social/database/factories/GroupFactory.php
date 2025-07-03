<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Group;
use IJIDeals\Social\Models\GroupCategory;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    /**
     * @var class-string<\IJIDeals\Social\Models\Group>
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->unique()->company;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph,
            'privacy' => $this->faker->randomElement(['public', 'private', 'restricted']),
            'creator_id' => User::factory(),
            'group_category_id' => GroupCategory::factory(), // Assuming GroupCategory and its factory exist
            'settings' => [
                'allow_member_posts' => true,
                'require_post_approval' => false,
            ],
            'is_verified' => $this->faker->boolean(25), // 25% chance of being true
            'allow_events' => $this->faker->boolean(75),
            'auto_approve_members' => $this->faker->boolean(50),
            'auto_approve_posts' => $this->faker->boolean(50),
            'last_activity_at' => now(),
        ];
    }

    /**
     * Indicate that the group is public.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function public()
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy' => 'public',
            ];
        });
    }

    /**
     * Indicate that the group is private.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy' => 'private',
            ];
        });
    }
}
