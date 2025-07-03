<?php

namespace Acme\SecureMessaging\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Acme\SecureMessaging\Models\Group;
use Acme\SecureMessaging\Models\GroupMember; // Not directly used here but related
use App\Models\User; // Assuming App\Models\User

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition()
    {
        $userModel = config('messaging.user_model', \App\Models\User::class);
        return [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->company . ' Testers',
            'description' => $this->faker->sentence,
            'created_by_user_id' => $userModel::factory(),
            // avatar_url can be added if needed
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Group $group) {
            // Automatically add the creator as an admin member
            // This logic is also in GroupController@store, but good for factory consistency
            // However, the Group model's boot method already creates a conversation
            // and the controller adds the creator as admin.
            // Here, we just ensure the Group object is consistent if created standalone by factory.
            // If the Group's boot method or controller handles member creation & conversation,
            // this might be redundant or could conflict if not careful.
            // For testing, it's often simpler if the factory just creates the Group row.
            // The specific test setup can then add members as needed.

            // Let's remove automatic member/conversation creation from here to avoid conflicts
            // with model events or controller logic being tested.
            // Tests should explicitly set up members and check conversation creation.
        });
    }
}
