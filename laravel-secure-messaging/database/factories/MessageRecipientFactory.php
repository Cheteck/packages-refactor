<?php

namespace Acme\SecureMessaging\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Acme\SecureMessaging\Models\MessageRecipient;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\Conversation;
use App\Models\User; // Assuming App\Models\User

class MessageRecipientFactory extends Factory
{
    protected $model = MessageRecipient::class;

    public function definition()
    {
        $userModel = config('messaging.user_model', \App\Models\User::class);

        return [
            'message_id' => Message::factory(),
            'user_id' => $userModel::factory(),
            'conversation_id' => function (array $attributes) {
                // Ensure conversation_id matches the message's conversation_id
                return Message::find($attributes['message_id'])->conversation_id;
            },
            'content' => $this->faker->asciify('***********'), // Placeholder for user-specific encrypted content
            'read_at' => null,
            'delivered_at' => now(),
        ];
    }

    public function unread()
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => null,
            ];
        });
    }

    public function read()
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => now(),
            ];
        });
    }
}
