<?php

namespace IJIDeals\Social\Database\Factories;

use App\Enums\ActivityTypeEnum;
use IJIDeals\Social\Models\Activity;
use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $subject = Post::factory()->create(); // Default to a Post subject
        $type = $this->faker->randomElement(ActivityTypeEnum::cases());

        return [
            'type' => $type->value,
            'description' => Activity::getDefaultDescription($type), // Use the static method from the model
            'user_id' => User::factory(),
            'subject_id' => $subject->id,
            'subject_type' => get_class($subject), // Or Post::class directly
        ];
    }

    /**
     * Indicate the activity is for a specific subject.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forSubject(\Illuminate\Database\Eloquent\Model $subject)
    {
        return $this->state(function (array $attributes) use ($subject) {
            return [
                'subject_id' => $subject->id,
                'subject_type' => get_class($subject),
            ];
        });
    }

    /**
     * Indicate the activity is of a specific type.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ofType(ActivityTypeEnum $type)
    {
        return $this->state([
            'type' => $type->value,
            'description' => Activity::getDefaultDescription($type),
        ]);
    }
}
