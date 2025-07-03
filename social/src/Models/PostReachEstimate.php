<?php

/**
 * PostReachEstimate model.
 */

namespace IJIDeals\Social\Models; // Changed namespace

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Import OpenApi namespace

// Added use for Post model

/**
 * @OA\Schema(
 *     schema="PostReachEstimate",
 *     title="PostReachEstimate",
 *     description="Modèle pour stocker les estimations de portée des posts",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de l'estimation de portée"
 *     ),
 *     @OA\Property(
 *         property="post_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du post associé"
 *     ),
 *     @OA\Property(
 *         property="reach_estimate",
 *         type="number",
 *         format="float",
 *         description="Estimation de la portée calculée pour le post"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'enregistrement de l'estimation"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'enregistrement de l'estimation"
 *     )
 * )
 * Modèle pour stocker les estimations de portée des posts
 *
 * @property int $id
 * @property int $post_id ID du post principal
 * @property float $reach_estimate Estimation de la portée calculée
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PostReachEstimate forPost(int $postId) Scope pour filtrer par post
 */
class PostReachEstimate extends Model
{
    /**
     * Champs autorisés pour l'assignation de masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'post_id',
        'reach_estimate',
    ];

    /**
     * Casts des attributs
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reach_estimate' => 'float',
    ];

    /**
     * Relation avec le post associé
     */
    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope pour filtrer par post
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $postId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPost($query, $postId)
    {
        return $query->where('post_id', $postId);
    }
}
