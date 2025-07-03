<?php

namespace IJIDeals\Location\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;
use Packages\Location\Models\Country;

/**
 * @OA\Schema(
 *     schema="CountryTranslation",
 *     title="CountryTranslation",
 *     description="Modèle de traduction pour les noms de pays",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la traduction du pays"
 *     ),
 *     @OA\Property(
 *         property="country_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du pays associé à cette traduction"
 *     ),
 *     @OA\Property(
 *         property="locale",
 *         type="string",
 *         description="Code de la langue de la traduction (e.g., 'en', 'fr')"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom traduit du pays"
 *     )
 * )
 *
 * Désactive la gestion des timestamps
 *
 * @var bool
 *
 * Attributs mass assignable
 * @var array
 *
 * Nom de la table de traduction
 * @var string
 */
class CountryTranslation extends Model
{
    /**
     * Désactive la gestion des timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributs mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'name',         // Nom du pays traduit dans la locale
        // 'locale' and 'country_id' are handled by Astrotomic
    ];

    /**
     * Nom de la table de traduction
     *
     * @var string
     */
    protected $table = 'country_translations';

    /**
     * Get the country that this translation belongs to.
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
