<?php

namespace IJIDeals\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SocialNetwork",
 *     title="SocialNetwork",
 *     description="Modèle pour gérer les réseaux sociaux et leurs propriétés",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du réseau social"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom du réseau social (e.g., 'Facebook', 'Twitter')"
 *     ),
 *     @OA\Property(
 *         property="icon",
 *         type="string",
 *         nullable=true,
 *         description="Chemin ou classe CSS de l'icône du réseau social"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="url",
 *         description="URL de base du réseau social"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description du réseau social"
 *     ),
 *     @OA\Property(
 *         property="priority",
 *         type="integer",
 *         nullable=true,
 *         description="Priorité d'affichage du réseau social"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="boolean",
 *         description="Statut d'activation du réseau social (true pour actif, false pour inactif)"
 *     ),
 *     @OA\Property(
 *         property="button_text",
 *         type="string",
 *         nullable=true,
 *         description="Texte à afficher sur un bouton de partage pour ce réseau"
 *     ),
 *     @OA\Property(
 *         property="button_color",
 *         type="string",
 *         nullable=true,
 *         description="Couleur du bouton de partage (code hexadécimal ou nom CSS)"
 *     ),
 *     @OA\Property(
 *         property="icon_color",
 *         type="string",
 *         nullable=true,
 *         description="Couleur de l'icône du réseau social"
 *     ),
 *     @OA\Property(
 *         property="share_template",
 *         type="string",
 *         nullable=true,
 *         description="Modèle d'URL pour le partage de contenu sur ce réseau"
 *     ),
 *     @OA\Property(
 *         property="meta_tags",
 *         type="object",
 *         nullable=true,
 *         description="Meta tags spécifiques au réseau social (JSON)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'enregistrement"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'enregistrement"
 *     )
 * )
 * Modèle pour gérer les réseaux sociaux.
 *
 * Ce modèle représente les différents réseaux sociaux qui peuvent être utilisés
 * dans l'application. Il contient des informations telles que le nom du réseau,
 * l'icône associée, l'URL, une description, la priorité d'affichage,
 * l'état d'activation, le texte du bouton, les couleurs du bouton et de l'icône,
 * un modèle de partage et des meta tags.
 *
 * @property int $id
 * @property string $name
 * @property string|null $icon
 * @property string $url
 * @property string|null $description
 * @property int|null $priority
 * @property bool $status
 * @property string|null $button_text
 * @property string|null $button_color
 * @property string|null $icon_color
 * @property string|null $share_template
 * @property array|null $meta_tags
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * TODO: Ajouter des relations avec d'autres modèles si nécessaire (ex: Utilisateur).
 * TODO: Implémenter des scopes pour filtrer les réseaux sociaux actifs ou par priorité.
 * TODO: Ajouter des règles de validation pour les champs lors de la création ou de la mise à jour.
 * TODO: Envisager l'ajout d'un champ pour stocker des informations d'authentification spécifiques au réseau social (si nécessaire).
 */
class SocialNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'url',
        'description',
        'priority',
        'status',
        'button_text',
        'button_color',
        'icon_color',
        'share_template',
        'meta_tags',
    ];

    protected $casts = [
        'status' => 'boolean',
        'meta_tags' => 'array',
    ];
}
