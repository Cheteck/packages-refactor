<?php

namespace IJIDeals\Location\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CityTranslation",
 *     title="CityTranslation",
 *     description="Modèle de traduction pour les noms de villes",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la traduction de la ville"
 *     ),
 *     @OA\Property(
 *         property="city_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de la ville associée à cette traduction"
 *     ),
 *     @OA\Property(
 *         property="locale",
 *         type="string",
 *         description="Code de la langue de la traduction (e.g., 'en', 'fr')"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom traduit de la ville"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la traduction"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la traduction"
 *     )
 * )
 */
class CityTranslation extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city_id',
        'locale',
        'name',
    ];

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'city_translations';

    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
