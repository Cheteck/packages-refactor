<?php

namespace Acme\SecureMessaging\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\Conversation;
use App\Models\User; // Assuming App\Models\User, adjust if your test user model is different

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        // Try to resolve User and Conversation factories from the application or test setup first
        $userModel = config('messaging.user_model', \App\Models\User::class);

        return [
            'uuid' => $this->faker->uuid,
            'conversation_id' => Conversation::factory(),
            'sender_id' => $userModel::factory(),
            'content' => $this->faker->asciify('***********'), // Placeholder for encrypted content
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
