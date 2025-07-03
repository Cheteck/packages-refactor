<?php

namespace IJIDeals\Analytics\Models;

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OpenApi\Annotations as OA;

// use IJIDeals\Analytics\Models\User;

/**
 * @OA\Schema(
 *     schema="TrackableInteraction",
 *     title="TrackableInteraction",
 *     description="Modèle pour le suivi des interactions des utilisateurs avec des entités traçables",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="trackable_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="trackable_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="interaction_type",
 *         type="string",
 *         enum={"like", "share", "comment", "favorite", "follow"}
 *     ),
 *     @OA\Property(
 *         property="details",
 *         type="object"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class TrackableInteraction extends Model
{
    protected $fillable = [
        'trackable_id',
        'trackable_type',
        'user_id',
        'interaction_type',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Types d'interactions valides
     */
    public const VALID_TYPES = [
        'like',
        'share',
        'comment',
        'favorite',
        'follow',
    ];

    /**
     * Relation avec l'entité trackable
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le type d'interaction est valide
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES);
    }

    /**
     * Vérifie si l'interaction est valide avant la création
     */
    protected static function booted(): void
    {
        static::creating(function ($interaction) {
            if (! self::isValidType($interaction->interaction_type)) {
                throw new \InvalidArgumentException(
                    "Invalid interaction type: {$interaction->interaction_type}. ".
                    'Valid types are: '.implode(', ', self::VALID_TYPES)
                );
            }
        });
    }
}
