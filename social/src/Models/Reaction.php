<?php

namespace IJIDeals\Social\Models;

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Reaction",
 *     title="Reaction",
 *     description="Modèle représentant une réaction (like, love, etc.) à une entité",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="interactable_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="interactable_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string"
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
class Reaction extends Model
{
    /** @var int */
    public $id;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'interactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'interactable_id',
        'interactable_type',
        'type',
    ];

    /**
     * Get the interactable entity that the interaction belongs to.
     */
    public function interactable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that created the interaction.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add or update an interaction to an interactable entity.
     * If an interaction by the current user already exists for the given interactable,
     * its type will be updated. Otherwise, a new interaction will be created.
     *
     * @param  Model&object{id: int, getMorphClass: callable}  $interactable  The model instance being interacted to.
     * @param  string  $type  The type of interaction (e.g., 'like', 'love', 'haha').
     * @return Reaction The created or updated Reaction model instance.
     *
     * @throws \RuntimeException If no authenticated user is found.
     */
    public static function addInteraction(Model $interactable, string $type): Reaction
    {
        $userId = static::getAuthenticatedUserId();

        return self::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'interactable_id' => $interactable->id,
                'interactable_type' => $interactable->getMorphClass(),
            ],
            [
                'type' => $type,
            ]
        );
    }

    /**
     * Remove an interaction from an interactable entity for the current authenticated user.
     *
     * @param  Model&object{id: int, getMorphClass: callable}  $interactable  The model instance from which the interaction is to be removed.
     * @return bool True if the interaction was deleted, false otherwise.
     *
     * @throws \RuntimeException If no authenticated user is found.
     */
    public static function removeInteraction(Model $interactable): bool
    {
        $userId = static::getAuthenticatedUserId();

        return self::query()->where('user_id', $userId)
            ->where('interactable_id', $interactable->id)
            ->where('interactable_type', $interactable->getMorphClass())
            ->delete();
    }

    /**
     * Check if the current authenticated user has interacted with an interactable entity.
     *
     * @param  Model&object{id: int, getMorphClass: callable}  $interactable  The model instance to check for interactions.
     * @return bool True if the authenticated user has interacted, false otherwise.
     *
     * @throws \RuntimeException If no authenticated user is found.
     */
    public static function hasInteracted(Model $interactable): bool
    {
        $userId = static::getAuthenticatedUserId();

        return self::query()->where('user_id', $userId)
            ->where('interactable_id', $interactable->id)
            ->where('interactable_type', $interactable->getMorphClass())
            ->exists();
    }

    /**
     * Helper method to get the authenticated user ID.
     *
     * @return int The ID of the authenticated user.
     *
     * @throws \RuntimeException If no authenticated user is found.
     */
    protected static function getAuthenticatedUserId(): int
    {
        $userId = auth()->id();

        if (is_null($userId)) {
            throw new \RuntimeException('No authenticated user found for this operation.');
        }

        return $userId;
    }
}
