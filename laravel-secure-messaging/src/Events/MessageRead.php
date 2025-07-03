<?php

namespace Acme\SecureMessaging\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue; // Add this
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Acme\SecureMessaging\Models\MessageRecipient;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\Conversation;

class MessageRead implements ShouldBroadcast, ShouldQueue // Implement ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public Conversation $conversation;
    public int $readerUserId; // ID of the user who read the message
    public string $readAt;

    /**
     * Create a new event instance.
     *
     * @param MessageRecipient $messageRecipient
     */
    public function __construct(MessageRecipient $messageRecipient)
    {
        $this->message = $messageRecipient->message; // Assumes relation is loaded or loads it
        $this->conversation = $messageRecipient->conversation; // Assumes relation is loaded
        $this->readerUserId = $messageRecipient->user_id;
        $this->readAt = $messageRecipient->read_at->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * This event is broadcast on the conversation channel so all participants
     * can update their UI to show the message as read by this user.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('conversation.'.$this->conversation->uuid);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'message_uuid' => $this->message->uuid,
            'conversation_uuid' => $this->conversation->uuid,
            'reader_user_id' => $this->readerUserId,
            'read_at' => $this->readAt,
        ];
    }
}
