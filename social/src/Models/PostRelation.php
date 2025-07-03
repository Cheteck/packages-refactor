<?php

namespace IJIDeals\Social\Models; // Changed namespace

use Illuminate\Database\Eloquent\Model;

// Added use for Post model

/**
 * Modèle PostRelation pour gérer les relations entre posts et autres entités
 *
 * Ce modèle permet de créer des relations polymorphes entre un post et d'autres modèles
 * (ex: utilisateurs, hashtags, catégories). Il sert de table pivot pour ces relations.
 *
 * @property int $id
 * @property int $post_id ID du post principal
 * @property int $related_id ID de l'entité liée
 * @property string $related_type Type de l'entité liée (classe du modèle)
 * @property string $relation_type Type de relation (ex: 'author', 'category', 'hashtag')
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostRelation extends Model
{
    /**
     * Champs autorisés pour l'assignation de masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'post_id',       // ID du post principal
        'related_id',    // ID de l'entité liée
        'related_type',  // Type de l'entité liée (classe du modèle)
        'relation_type', // Type de relation (ex: 'author', 'category', 'hashtag')
    ];

    /**
     * Relation avec le post principal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relation polymorphe avec l'entité liée
     *
     * Cette méthode permet de relier le post à différents types d'entités
     * (utilisateurs, hashtags, catégories, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function related()
    {
        return $this->morphTo();
    }
}
