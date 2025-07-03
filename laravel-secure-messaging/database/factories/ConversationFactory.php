<?php

namespace Acme\SecureMessaging\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Acme\SecureMessaging\Models\Conversation;
use App\Models\User; // Assuming App\Models\User

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'type' => 'individual', // Default to individual
            'last_message_at' => now(),
            // group_id would be set specifically for group conversations
        ];
    }

    public function groupType()
    {
        return $this->state(function (array $attributes) {
            // This would typically also need a group_id from a GroupFactory
            // For now, just setting the type.
            return [
                'type' => 'group',
            ];
        });
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Conversation $conversation) {
            if ($conversation->type === 'individual') {
                // Attach 2 participants to individual conversations by default
                $userModel = config('messaging.user_model', \App\Models\User::class);
                $users = $userModel::factory()->count(2)->create();
                $conversation->participants()->attach($users->pluck('id'));
            }
            // For group conversations, participants are usually added when GroupMembers are created.
        });
    }
}
