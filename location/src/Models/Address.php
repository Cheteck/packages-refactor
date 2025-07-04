<?php

namespace IJIDeals\Location\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

// Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="Address",
 *     title="Address",
 *     description="Modèle d'adresse pour diverses entités (utilisateurs, boutiques, etc.)",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de l'adresse"
 *     ),
 *     @OA\Property(
 *         property="addressable_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du modèle parent auquel l'adresse est attachée"
 *     ),
 *     @OA\Property(
 *         property="addressable_type",
 *         type="string",
 *         description="Type du modèle parent auquel l'adresse est attachée (polymorphique)"
 *     ),
 *     @OA\Property(
 *         property="street",
 *         type="string",
 *         description="Nom de la rue et numéro"
 *     ),
 *     @OA\Property(
 *         property="postal_code",
 *         type="string",
 *         description="Code postal"
 *     ),
 *     @OA\Property(
 *         property="city_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de la ville associée à l'adresse"
 *     ),
 *     @OA\Property(
 *         property="region_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true,
 *         description="ID de la région/état associée à l'adresse"
 *     ),
 *     @OA\Property(
 *         property="country_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du pays associé à l'adresse"
 *     ),
 *     @OA\Property(
 *         property="latitude",
 *         type="number",
 *         format="float",
 *         nullable=true,
 *         description="Latitude géographique de l'adresse"
 *     ),
 *     @OA\Property(
 *         property="longitude",
 *         type="number",
 *         format="float",
 *         nullable=true,
 *         description="Longitude géographique de l'adresse"
 *     ),
 *     @OA\Property(
 *         property="formatted_address",
 *         type="string",
 *         nullable=true,
 *         description="Adresse formatée complète"
 *     ),
 *     @OA\Property(
 *         property="is_primary",
 *         type="boolean",
 *         description="Indique si c'est l'adresse principale de l'entité"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         nullable=true,
 *         description="Type d'adresse (e.g., 'shipping', 'billing', 'home')"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'adresse"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'adresse"
 *     )
 * )
 * Modèle d'adresse utilisateur
 */
class Address extends Model
{
    // use AddressFormatter;
    // use AddressSearchable;
    // use HasAddressValidation;

    /** @var int */
    public $id;

    /** @var int */
    public $addressable_id;

    /** @var string */
    public $addressable_type;

    /** @var string|null */
    public $street;

    /** @var string|null */
    public $postal_code;

    /** @var int|null */
    public $city_id;

    /** @var int|null */
    public $region_id;

    /** @var int|null */
    public $country_id;

    /** @var float|null */
    public $latitude;

    /** @var float|null */
    public $longitude;

    /** @var string|null */
    public $formatted_address;

    /** @var bool */
    public $is_primary;

    /** @var string|null */
    public $type;

    /** @var City|null */
    public $city;

    /** @var Region|null */
    public $region;

    /** @var Country|null */
    public $country;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'street',
        'postal_code',
        'city_id', // Changed to a city_id foreign key
        'region_id', // Added region_id foreign key
        'country_id', // Added country_id foreign key
        'latitude',
        'longitude',
        'formatted_address',
        'is_primary',
        'type',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Morph relationship
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    // Define relationships with City, Region, and Country

    // An Address belongs to a City
    public function city(): BelongsTo
    {
        // Assurez-vous que le modèle City existe et est correctement importé si nécessaire
        return $this->belongsTo(City::class); // This will now resolve to Packages\Location\Models\City
    }

    // An Address belongs to a Region via the City (or directly if desired)
    public function region(): BelongsTo
    {
        // Assurez-vous que le modèle Region existe et est correctement importé si nécessaire
        return $this->belongsTo(Region::class, 'region_id');
    }

    // An Address belongs to a Country via the City (or directly if desired)
    public function country(): BelongsTo
    {
        // Assurez-vous que le modèle Country existe et est correctement importé si nécessaire
        return $this->belongsTo(Country::class, 'country_id'); // Will now resolve to Packages\Location\Models\Country
    }

    /**
     * Validate the address data.
     */
    public function validate(): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'addressable_id' => 'required|integer',
            'addressable_type' => 'required|string',
            'street' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'city_id' => 'required|exists:cities,id',
            'region_id' => 'nullable|exists:regions,id',
            'country_id' => 'required|exists:countries,id',
            'latitude' => 'nullable|numeric|between:-90,90', // Added validation for lat/lng ranges
            'longitude' => 'nullable|numeric|between:-180,180',
            'formatted_address' => 'nullable|string|max:500',
            'is_primary' => 'boolean',
            'type' => 'nullable|string|max:100',
        ];

        return Validator::make($this->attributes, $rules);
    }

    public static function suggest(string $query)
    {
        // Example logic: Find addresses that match the query in street, city, or postal code
        return self::query()->where('street', 'like', "%$query%")
            ->orWhere('postal_code', 'like', "%$query%")
            ->orWhereHas('city', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', "%$query%"); // Assumes City model is translatable and 'name' is the attribute
            })
            ->orWhereHas('region', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', "%$query%"); // Assumes Region model is translatable
            })
            ->orWhereHas('country', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', "%$query%"); // Assumes Country model is translatable
            })
            ->get();
    }

    /**
     * Accès à l'adresse complète formatée
     */
    public function getFullAddressAttribute(): string
    {
        return $this->formatAddress();
    }

    /**
     * Nom du pays associé
     */
    public function getCountryNameAttribute(): ?string
    {
        return $this->country?->name; // Will access translated name if Country model is translatable
    }

    /**
     * Nom de la région associée
     */
    public function getRegionNameAttribute(): ?string
    {
        return $this->region?->name; // Will access translated name if Region model is translatable
    }

    /**
     * Nom de la ville associée
     */
    public function getCityNameAttribute(): ?string
    {
        return $this->city?->name; // Will access translated name if City model is translatable
    }

    // Removed redundant scopeWhere
    // public function scopeWhere(
    //     $query,
    //     $column,
    //     $operator = null,
    //     $value = null
    // ) {
    //     return $query->where($column, $operator, $value);
    // }

    /**
     * Formatage de l'adresse
     */
    private function formatAddress(): string
    {
        $components = [
            $this->street, // Utiliser le champ 'street' défini dans $fillable
            $this->postal_code,
            $this->city?->name,
            $this->region?->name,
            $this->country?->name,
        ];

        return implode(
            ', ',
            array_filter($components, function ($value) {
                return ! empty($value);
            })
        );
    }

    /**
     * Calculate the distance between two addresses using the Haversine formula.
     *
     * @param Address $address1
     * @param Address $address2
     * @return float Distance in kilometers
     */
    public static function calculateDistance(Address $address1, Address $address2): float
    {
        if (is_null($address1->latitude) || is_null($address1->longitude) ||
            is_null($address2->latitude) || is_null($address2->longitude)) {
            return 0.0; // Or throw an exception if lat/lng are required
        }

        $earthRadius = 6371; // kilometers

        $latFrom = deg2rad($address1->latitude);
        $lonFrom = deg2rad($address1->longitude);
        $latTo = deg2rad($address2->latitude);
        $lonTo = deg2rad($address2->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + 
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
