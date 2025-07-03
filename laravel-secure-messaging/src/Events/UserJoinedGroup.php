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

class UserJoinedGroup implements ShouldBroadcast, ShouldQueue // Implement ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;
    public array $joinedUserData; // User data for the user who joined

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user The user who joined
     * @param Group $group The group that was joined
     */
    public function __construct(\Illuminate\Contracts\Auth\Authenticatable $user, Group $group)
    {
        $this->group = $group;
        $this->joinedUserData = [
            'id' => $user->id,
            'name' => $user->name, // Assuming 'name' attribute exists
            // Add other relevant public user data from config('messaging.user_model_public_columns')
        ];
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
        // Fallback or error if group has no conversation - should not happen with current logic
        return [];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'group.user.joined';
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
            'user' => $this->joinedUserData,
        ];
    }
}
