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
use Acme\SecureMessaging\Models\Group;
use App\Models\User; // Will be replaced by config('messaging.user_model') instance

class UserLeftGroup implements ShouldBroadcast, ShouldQueue // Implement ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;
    public int $leftUserId; // ID of the user who left

    /**
     * Create a new event instance.
     *
     * @param int $userId The ID of the user who left
     * @param Group $group The group that was left
     */
    public function __construct(int $userId, Group $group)
    {
        $this->group = $group;
        $this->leftUserId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcast to the group's conversation channel.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        if ($this->group->conversation) {
            return new PrivateChannel('conversation.'.$this->group->conversation->uuid);
        }
        return [];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'group.user.left';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'group_id' => $this->group->id,
            'group_uuid' => $this->group->uuid,
            'group_name' => $this->group->name,
            'conversation_uuid' => $this->group->conversation ? $this->group->conversation->uuid : null,
            'user_id' => $this->leftUserId,
        ];
    }
}
