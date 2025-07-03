<?php

namespace IJIDeals\Social\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SocialLink",
 *     title="SocialLink",
 *     description="Modèle pour gérer les liens vers les réseaux sociaux associés à d'autres entités",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du lien social"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="url",
 *         description="L'URL complète du profil sur le réseau social"
 *     ),
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         nullable=true,
 *         description="Le nom d'utilisateur sur le réseau social"
 *     ),
 *     @OA\Property(
 *         property="social_network_id",
 *         type="integer",
 *         format="int64",
 *         description="L'identifiant du réseau social associé"
 *     ),
 *     @OA\Property(
 *         property="socialable_id",
 *         type="integer",
 *         format="int64",
 *         description="L'identifiant de l'entité associée (utilisateur, boutique, etc.)"
 *     ),
 *     @OA\Property(
 *         property="socialable_type",
 *         type="string",
 *         description="Le type de l'entité associée (nom de la classe)"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Une description optionnelle du lien"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du lien social"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du lien social"
 *     )
 * )
 * Modèle pour gérer les liens vers les réseaux sociaux associés à d'autres entités.
 *
 * Ce modèle représente un lien vers un réseau social spécifique, associé à une autre entité
 * via une relation polymorphique. Il permet de lier des profils d'utilisateurs, des boutiques,
 * ou d'autres entités à leurs comptes sur divers réseaux sociaux.
 *
 * Les attributs principaux du modèle SocialLink sont :
 *
 * - url : L'URL complète du profil sur le réseau social (obligatoire, doit être une URL valide).
 * - username : Le nom d'utilisateur sur le réseau social.
 * - social_network_id : L'identifiant du réseau social associé (clé étrangère vers la table social_networks).
 * - socialable_id : L'identifiant de l'entité associée (utilisateur, boutique, etc.).
 * - socialable_type : Le type de l'entité associée (nom de la classe).
 * - description : Une description optionnelle du lien.
 *
 * Le modèle utilise les relations Eloquent suivantes :
 *
 * - socialNetwork : Relation "belongsTo" vers le modèle SocialNetwork, définissant le réseau social.
 * - socialable : Relation polymorphique "morphTo" permettant de lier le lien à différents types d'entités.
 *
 * - TODO: Ajouter des règles de validation pour l'URL afin de s'assurer qu'elle est bien formée.
 * - TODO: S'assurer que le social_network_id existe bien dans la table social_networks lors de la création ou de la mise à jour.
 * - TODO: Envisager d'ajouter un système de cache pour les relations afin d'améliorer les performances.
 * - TODO: Documenter l'utilisation de la relation polymorphique pour les développeurs.
 * - TODO: Ajouter des tests unitaires pour les relations et les validations du modèle.
 *
 * use Illuminate\\Database\\Eloquent\\SoftDeletes;
 */
class SocialLink extends Model
{
    // use SoftDeletes;

    protected $fillable = ['url', 'username', 'social_network_id', 'socialable_id', 'socialable_type', 'description'];

    protected $casts = [
        'socialable_id' => 'integer',
        'social_network_id' => 'integer',
    ];

    public function socialNetwork()
    {
        return $this->belongsTo(SocialNetwork::class);
    }

    public function socialable()
    {
        return $this->morphTo();
    }
}
