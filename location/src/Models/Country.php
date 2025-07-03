<?php

namespace IJIDeals\Location\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA; // Added for slug generation

/**
 * @OA\Schema(
 *     schema="Country",
 *     title="Country",
 *     description="Modèle représentant un pays",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du pays"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom du pays"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Slug unique du pays pour les URL"
 *     ),
 *     @OA\Property(
 *         property="iso2_code",
 *         type="string",
 *         description="Code ISO 3166-1 alpha-2 du pays (e.g., US, FR, DE)"
 *     ),
 *     @OA\Property(
 *         property="iso3_code",
 *         type="string",
 *         description="Code ISO 3166-1 alpha-3 du pays (e.g., USA, FRA, DEU)"
 *     ),
 *     @OA\Property(
 *         property="phone_code",
 *         type="string",
 *         nullable=true,
 *         description="Code téléphonique international du pays (e.g., +1, +33)"
 *     ),
 *     @OA\Property(
 *         property="currency_code",
 *         type="string",
 *         nullable=true,
 *         description="Code de devise ISO 4217 du pays (e.g., USD, EUR)"
 *     ),
 *     @OA\Property(
 *         property="flag_emoji",
 *         type="string",
 *         nullable=true,
 *         description="Émoji du drapeau du pays (e.g., 🇺🇸, 🇫🇷)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du pays"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du pays"
 *     )
 * )
 * Represents a country in the system.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $iso2_code // e.g., US, CA, GB (ISO 3166-1 alpha-2)
 * @property string $iso3_code // e.g., USA, CAN, GBR (ISO 3166-1 alpha-3)
 * @property string|null $phone_code // e.g., +1, +44 (International Dialing Code)
 * @property string|null $currency_code // e.g., USD, CAD, GBP (ISO 4217 currency code)
 * @property string|null $flag_emoji // e.g., 🇺🇸, 🇨🇦, 🇬🇧
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, City> $cities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Region> $regions
 */
class Country extends Model implements TranslatableContract
{
    use HasFactory, Sluggable, Translatable; // Added Translatable and Sluggable

    public $translatedAttributes = ['name']; // 'official_name', 'description' could be added

    public $translationModel = CountryTranslation::class;

    public $translationForeignKey = 'country_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'name' is now in translations
        'slug',
        'iso2_code',
        'iso3_code',
        'phone_code',
        'currency_code',
        'flag_emoji',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No specific casts needed for these string fields, but good to have the array.
    ];

    /**
     * The "booted" method of the model.
     *
     * Automatically generates a slug from the name if not provided.
     */
    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name', // Sluggable will use the name from the current locale
            ],
        ];
    }

    /**
     * The "booted" method of the model.
     *
     * No longer needed for slug generation if using Sluggable trait.
     */
    // protected static function booted(): void
    // {
    //     static::creating(function (Country $country) {
    //         if (empty($country->slug)) {
    //             $country->slug = Str::slug($country->name);
    //         }
    //     });

    //     static::updating(function (Country $country) {
    //         // Only update slug if name changes and slug is not explicitly set
    //         if ($country->isDirty('name') && empty($country->slug)) {
    //             $country->slug = Str::slug($country->name);
    //         }
    //     });
    // }

    /**
     * Get the cities associated with the country.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get the regions/states associated with the country.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Get the full flag string (emoji + name).
     */
    public function getFullFlagAttribute(): string
    {
        return ($this->flag_emoji ? $this->flag_emoji.' ' : '').$this->name;
    }

    /**
     * Find a country by its ISO2 code.
     *
     * @param  string  $iso2Code  The ISO 3166-1 alpha-2 code (e.g., 'US').
     */
    public static function findByIso2(string $iso2Code): ?static
    {
        return static::where('iso2_code', strtoupper($iso2Code))->first();
    }

    /**
     * Find a country by its ISO3 code.
     *
     * @param  string  $iso3Code  The ISO 3166-1 alpha-3 code (e.g., 'USA').
     */
    public static function findByIso3(string $iso3Code): ?static
    {
        return static::where('iso3_code', strtoupper($iso3Code))->first();
    }

    /**
     * Find a country by its slug.
     *
     * @param  string  $slug  The URL-friendly slug of the country.
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }
}
