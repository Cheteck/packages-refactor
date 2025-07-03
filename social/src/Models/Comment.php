<?php

namespace IJIDeals\Social\Models; // Changed namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="Comment",
 *     title="Comment",
 *     description="Modèle représentant un commentaire laissé sur une entité (post, photo, etc.)",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="author_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="author_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="commentable_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="commentable_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="parent_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
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
class Comment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \IJIDeals\Social\Database\Factories\CommentFactory::new();
    }

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array
     */
    protected $fillable = [
        'author_id',
        'author_type',
        'commentable_id',
        'commentable_type',
        'content',
        'parent_id',
    ];

    /**
     * Obtient l'entité commentable à laquelle le commentaire appartient.
     */
    public function commentable(): MorphTo
    {
        Log::info('Récupération de l\'entité commentable pour le commentaire.', ['comment_id' => $this->id]);

        return $this->morphTo();
    }

    /**
     * Obtient l'auteur du commentaire.
     */
    public function author(): MorphTo
    {
        Log::info('Récupération de l\'auteur du commentaire.', ['comment_id' => $this->id]);

        return $this->morphTo();
    }

    /**
     * Obtient les réponses à ce commentaire.
     */
    public function replies(): HasMany
    {
        Log::info('Récupération des réponses au commentaire.', ['comment_id' => $this->id]);

        return $this->hasMany(Comment::class, 'parent_id');
    }
}
