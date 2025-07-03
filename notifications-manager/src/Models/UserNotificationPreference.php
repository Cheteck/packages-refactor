<?php

namespace IJIDeals\NotificationsManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="UserNotificationPreference",
 *     title="User Notification Preference",
 *     description="User's notification preference setting for a specific type and channel.",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID of the notification preference"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID of the user"
 *     ),
 *     @OA\Property(
 *         property="notification_type",
 *         type="string",
 *         description="Type of notification (e.g., 'new_message', 'order_shipped')"
 *     ),
 *     @OA\Property(
 *         property="channel",
 *         type="string",
 *         description="Channel for the notification (e.g., 'mail', 'database', 'push')"
 *     ),
 *     @OA\Property(
 *         property="is_enabled",
 *         type="boolean",
 *         description="Whether the notification is enabled or disabled"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp when the preference was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp when the preference was last updated"
 *     )
 * )
 */
class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $table;

    protected $fillable = [
        'user_id',
        'notification_type', // e.g., 'new_message', 'order_shipped'
        'channel',           // e.g., 'mail', 'database', 'push'
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('notifications-manager.table_names.user_notification_preferences', 'user_notification_preferences');
    }

    /**
     * Get the user that this preference belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('notifications-manager.user_model', \App\Models\User::class));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // Attempt to resolve the factory from the conventional path.
        // You'll need to create this factory: IJIDeals\NotificationsManager\Database\factories\UserNotificationPreferenceFactory
        return \IJIDeals\NotificationsManager\Database\factories\UserNotificationPreferenceFactory::new();
    }
}
