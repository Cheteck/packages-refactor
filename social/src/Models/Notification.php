<?php

namespace IJIDeals\Social\Models;

use Illuminate\Notifications\DatabaseNotification;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Notification",
 *     title="Notification",
 *     description="Notification model",
 *
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="notifiable_type", type="string"),
 *     @OA\Property(property="notifiable_id", type="integer"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Notification extends DatabaseNotification
{
    //
}
