<?php

namespace Acme\SecureMessaging\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Acme\SecureMessaging\Models\Conversation;
use App\Models\User; // Will be replaced by config('messaging.user_model') instance

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public array $typingUserData;
    public bool $isTyping;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user The user who is typing
     * @param Conversation $conversation The conversation where typing is happening
     * @param bool $isTyping True if typing, false if stopped typing
     */
    public function __construct(\Illuminate\Contracts\Auth\Authenticatable $user, Conversation $conversation, bool $isTyping)
    {
        $this->conversation = $conversation;
        $this->typingUserData = [
            'id' => $user->id,
            'name' => $user->name, // Assuming 'name' attribute exists
        ];
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcast to the conversation channel.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // This should be a presence channel if you want to easily know who is "in" the conversation.
        // However, for typing indicators, a private channel is often sufficient.
        // The client side logic will handle displaying "User X is typing..."
        // We don't want the user who is typing to receive their own typing event as a "is typing" notification.
        // Laravel's `broadcast(...)->toOthers()` handles this for Echo.
        return new PrivateChannel('conversation.'.$this->conversation->uuid);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'typing.indicator';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'conversation_uuid' => $this->conversation->uuid,
            'user' => $this->typingUserData,
            'is_typing' => $this->isTyping,
        ];
    }
}
