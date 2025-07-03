<?php

namespace IJIDeals\Location\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Spatie\Translatable\HasTranslations; // Replaced with Astrotomic
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Added
use OpenApi\Annotations as OA; // Added

/**
 * @OA\Schema(
 *     schema="Region",
 *     title="Region",
 *     description="Modèle représentant une région ou un état",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la région"
 *     ),
 *     @OA\Property(
 *         property="country_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du pays auquel la région appartient"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom de la région (traduisible)"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         nullable=true,
 *         description="Code de la région (e.g., 'CA' pour Californie)"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="boolean",
 *         description="Statut de la région (true pour actif, false pour inactif)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la région"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la région"
 *     )
 * )
 */
class Region extends Model implements TranslatableContract
{
    use HasFactory, Sluggable, Translatable; // Replaced HasTranslations, added HasFactory, Sluggable

    public $translatedAttributes = ['name', 'description']; // Added description as potentially translatable

    public $translationModel = RegionTranslation::class; // To be created

    public $translationForeignKey = 'region_id';

    // Removed TODOs as they seem outdated or addressed by package structure

    protected $fillable = [
        'country_id',
        // 'name' is now translatable
        'slug', // Added slug
        'code', // Region code, e.g., CA for California
        'status',
    ];

    // Removed public $translatable = ['name'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name', // Will use translated name
            ],
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
