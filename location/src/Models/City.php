<?php

namespace IJIDeals\Location\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable; // Added
// use IJIDeals\Location\Traits\HasCities; // Removed, as it was empty and self-referential
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes; // Added
use OpenApi\Annotations as OA; // Added

/**
 * @OA\Schema(
 *     schema="City",
 *     title="City",
 *     description="Modèle représentant une ville",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la ville"
 *     ),
 *     @OA\Property(
 *         property="country_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du pays auquel la ville appartient"
 *     ),
 *     @OA\Property(
 *         property="region_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de la région/état auquel la ville appartient"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom de la ville (traduisible)"
 *     ),
 *     @OA\Property(
 *         property="postal_code",
 *         type="string",
 *         nullable=true,
 *         description="Code postal de la ville"
 *     ),
 *     @OA\Property(
 *         property="latitude",
 *         type="number",
 *         format="float",
 *         description="Latitude géographique de la ville"
 *     ),
 *     @OA\Property(
 *         property="longitude",
 *         type="number",
 *         format="float",
 *         description="Longitude géographique de la ville"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="boolean",
 *         description="Statut de la ville (true pour actif, false pour inactif)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la ville"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la ville"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date de suppression douce de la ville"
 *     )
 * )
 * Modèle représentant une ville
 *
 * @property int $id
 * @property int $country_id
 * @property int $region_id
 * @property string $name
 * @property string $postal_code
 * @property float $latitude
 * @property float $longitude
 * @property bool $status
 * @property \App\Models\Country $country
 * @property \App\Models\Region $region
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class City extends Model implements TranslatableContract // Added Contract
{
    use HasFactory, Sluggable, SoftDeletes, Translatable; // Removed HasCities, Added HasFactory, Sluggable

    public string $translationModel = CityTranslation::class;

    public string $translationForeignKey = 'city_id'; // Explicitly define

    public array $translatedAttributes = ['name', 'description']; // Added description

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country_id',
        'region_id',
        // 'name' is now translatable
        'slug', // Added slug
        'postal_code',
        'latitude',
        'longitude',
        'status',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => 'boolean',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /**
     * Récupère le pays associé à la ville.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Récupère la région associée à la ville.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    // The translations() relationship is now provided by Astrotomic's Translatable trait.
    // public function translations(): HasMany
    // {
    //     return $this->hasMany(CityTranslation::class);
    // }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        // TODO: Create IJIDeals\Location\Database\factories\CityFactory
        return \IJIDeals\Location\Database\factories\CityFactory::new();
    }
}
