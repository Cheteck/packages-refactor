<?php

namespace IJIDeals\Social\Models; // Changed namespace

// use IJIDeals\UserManagement\Models\User; // Will use configured user model
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="Friendship",
 *     title="Friendship",
 *     description="Modèle représentant une relation d'amitié entre deux utilisateurs",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la relation d'amitié"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui a initié la demande d'amitié"
 *     ),
 *     @OA\Property(
 *         property="friend_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui est l'ami"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Statut de la relation d'amitié",
 *         enum={"pending", "accepted", "blocked"}
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la relation"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la relation"
 *     )
 * )
 */
class Friendship extends Model
{
    protected $fillable = [
        'user_id',
        'friend_id',
        'status', // pending, accepted, blocked
    ];

    public function user()
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class));
    }

    public function friend()
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class), 'friend_id');
    }
}
