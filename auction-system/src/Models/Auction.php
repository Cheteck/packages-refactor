<?php

namespace IJIDeals\AuctionSystem\Models;

use IJIDeals\Analytics\Traits\HasHistory;
use IJIDeals\AuctionSystem\Enums\AuctionStatusEnum;
use IJIDeals\IJICommerce\Models\IJICommerce\Product;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Auction",
 *     title="Auction",
 *     description="Modèle d'enchère pour les produits",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="product_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="starting_price",
 *         type="number",
 *         format="float"
 *     ),
 *     @OA\Property(
 *         property="current_price",
 *         type="number",
 *         format="float",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="reserve_price",
 *         type="number",
 *         format="float",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="increment_amount",
 *         type="number",
 *         format="float"
 *     ),
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"draft", "pending", "scheduled", "active", "ended", "cancelled", "sold", "failed", "exhausted_budget"}
 *     ),
 *     @OA\Property(
 *         property="winner_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="auto_extend",
 *         type="boolean"
 *     ),
 *     @OA\Property(
 *         property="extension_time",
 *         type="integer",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="min_bids",
 *         type="integer",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="featured",
 *         type="boolean"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
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
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     )
 * )
 *
 * @property int $id
 * @property int $product_id
 * @property float $starting_price
 * @property float|null $current_price
 * @property float|null $reserve_price
 * @property float $increment_amount
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property AuctionStatusEnum $status
 * @property int|null $winner_id
 * @property bool $auto_extend
 * @property int|null $extension_time
 * @property int|null $min_bids
 * @property bool $featured
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \IJIDeals\IJICommerce\Models\IJICommerce\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\AuctionSystem\Models\Bid[] $bids
 * @property-read \IJIDeals\UserManagement\Models\User|null $winner
 * @property-read \IJIDeals\UserManagement\Models\User|null $user // Assuming a general user relationship might exist or be added
 *
 * @method void recordActivity(string $event, array $properties = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Auction extends Model
{
    use HasFactory, HasHistory, SoftDeletes;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'starting_price',
        'current_price',
        'reserve_price',
        'increment_amount',
        'start_date',
        'end_date',
        'status',
        'winner_id',
        'auto_extend',
        'extension_time',
        'min_bids',
        'featured',
        'description',
    ];

    /**
     * Les attributs qui doivent être typés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'increment_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'auto_extend' => 'boolean',
        'extension_time' => 'integer',
        'min_bids' => 'integer',
        'featured' => 'boolean',
        'status' => AuctionStatusEnum::class,
    ];

    /**
     * Constantes de statut.
     */
    // const STATUS_DRAFT = 'draft';

    // const STATUS_PENDING = 'pending';

    // const STATUS_SCHEDULED = 'scheduled';

    // const STATUS_ACTIVE = 'active';

    // const STATUS_ENDED = 'ended';

    // const STATUS_CANCELLED = 'cancelled';

    // const STATUS_SOLD = 'sold';

    // const STATUS_FAILED = 'failed';

    /**
     * Obtenir le produit associé à cette enchère.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Obtenir les offres associées à cette enchère.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /**
     * Obtenir le gagnant de l'enchère.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * Vérifie si l'enchère est active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === AuctionStatusEnum::ACTIVE &&
               now()->between($this->start_date, $this->end_date);
    }

    /**
     * Vérifie si l'enchère est terminée.
     *
     * @return bool
     */
    public function isEnded()
    {
        return $this->status === AuctionStatusEnum::ENDED ||
               $this->status === AuctionStatusEnum::SOLD ||
               $this->status === AuctionStatusEnum::FAILED ||
               now()->greaterThan($this->end_date);
    }

    /**
     * Vérifie si l'enchère a atteint le prix de réserve.
     *
     * @return bool
     */
    public function isReserveMet()
    {
        return $this->current_price >= $this->reserve_price;
    }

    /**
     * Obtenir le montant minimum de la prochaine enchère.
     *
     * @return float
     */
    public function getNextBidAmount()
    {
        return $this->current_price + $this->increment_amount;
    }

    /**
     * Étendre la durée de l'enchère.
     *
     * @param  int  $minutes
     * @return void
     */
    public function extendDuration($minutes = null)
    {
        $minutes = $minutes ?: $this->extension_time;
        $this->end_date = $this->end_date->addMinutes($minutes);
        $this->save();
    }

    /**
     * Scope pour les enchères actives.
     */
    public function scopeActive($query)
    {
        return $query->where('status', AuctionStatusEnum::ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope pour les enchères à venir.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', AuctionStatusEnum::SCHEDULED)
            ->where('start_date', '>', now());
    }

    /**
     * Scope pour les enchères terminées.
     */
    public function scopeEnded($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', [AuctionStatusEnum::ENDED, AuctionStatusEnum::SOLD, AuctionStatusEnum::FAILED])
                ->orWhere('end_date', '<', now());
        });
    }

    /**
     * Scope pour les enchères en vedette.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
