<?php

namespace IJIDeals\Social\Models; // Changed namespace

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Added use for User model
use OpenApi\Annotations as OA; // Import OpenApi namespace

// Added use for PostPoll model

/**
 * @OA\Schema(
 *     schema="PostPollVote",
 *     title="PostPollVote",
 *     description="Modèle représentant un vote d'utilisateur pour une option de sondage",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du vote"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui a voté"
 *     ),
 *     @OA\Property(
 *         property="post_poll_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du sondage pour lequel l'utilisateur a voté"
 *     ),
 *     @OA\Property(
 *         property="option_id",
 *         type="integer",
 *         description="ID de l'option pour laquelle l'utilisateur a voté"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du vote"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du vote"
 *     )
 * )
 */
class PostPollVote extends Model
{
    // Colonnes qui peuvent être assignées massivement
    protected $fillable = [
        'user_id', // L'ID de l'utilisateur qui a voté
        'post_poll_id', // L'ID du sondage pour lequel l'utilisateur a voté
        'option_id', // L'ID de l'option pour laquelle l'utilisateur a voté
    ];

    // Relation : Un vote appartient à un utilisateur
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relation : Un vote appartient à un sondage
    public function poll(): BelongsTo
    {
        return $this->belongsTo(PostPoll::class);
    }
}
