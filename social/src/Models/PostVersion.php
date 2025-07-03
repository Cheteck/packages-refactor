<?php

namespace IJIDeals\Social\Models; // Changed namespace

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA; // Import OpenApi namespace

// Added use for Post model

/**
 * @OA\Schema(
 *     schema="PostVersion",
 *     title="PostVersion",
 *     description="Modèle représentant une version historique d'un post",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la version du post"
 *     ),
 *     @OA\Property(
 *         property="post_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du post auquel cette version est associée"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string",
 *         nullable=true,
 *         description="Contenu du post pour cette version"
 *     ),
 *     @OA\Property(
 *         property="version_number",
 *         type="integer",
 *         description="Numéro de version du post"
 *     ),
 *     @OA\Property(
 *         property="changes",
 *         type="object",
 *         nullable=true,
 *         description="Détails des modifications apportées à cette version (JSON)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de cette version"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de cette version"
 *     )
 * )
 */
class PostVersion extends Model
{
    protected $fillable = [
        'post_id',
        'content',
        'version_number',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
