<?php

namespace IJIDeals\AuctionSystem\Models;

use IJIDeals\AuctionSystem\Enums\BidStatusEnum;
// use IJIDeals\UserManagement\Models\User; // Will use configured user model
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Bid",
 *     title="Bid",
 *     description="Modèle d'offre pour les enchères",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="auction_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float"
 *     ),
 *     @OA\Property(
 *         property="auto_bid",
 *         type="boolean"
 *     ),
 *     @OA\Property(
 *         property="max_amount",
 *         type="number",
 *         format="float",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="outbid",
 *         type="boolean"
 *     ),
 *     @OA\Property(
 *         property="ip_address",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="user_agent",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "outbid", "winner", "cancelled", "rejected"}
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
 */
class Bid extends Model
{
    use HasFactory, SoftDeletes;

    /** @var bool */
    public $outbid;

    /** @var BidStatusEnum */
    public $status;

    /** @var bool */
    public $auto_bid;

    /** @var float|string */
    public $max_amount;

    /** @var ?Auction */
    public $auction;

    /** @var ?int */
    public $outbid_by_id;

    /** @var int */
    public $auction_id;

    /** @var int */
    public $user_id;

    /** @var float|string */
    public $amount;

    /** @var ?string */
    public $ip_address;

    /** @var ?string */
    public $user_agent;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
        'auto_bid',
        'max_amount',
        'outbid',
        'ip_address',
        'user_agent',
        'status',
    ];

    /**
     * Les attributs qui doivent être typés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'auto_bid' => 'boolean',
        'outbid' => 'boolean',
        'status' => BidStatusEnum::class,
    ];

    /**
     * Constantes de statut.
     */
    // const STATUS_ACTIVE = 'active';

    // const STATUS_OUTBID = 'outbid';

    // const STATUS_WINNER = 'winner';

    // const STATUS_CANCELLED = 'cancelled';

    // const STATUS_REJECTED = 'rejected';

    /**
     * Obtenir l'enchère associée à cette offre.
     */
    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * Obtenir l'utilisateur qui a fait cette offre.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class));
    }

    /**
     * Obtenir l'offre qui a surenchéri celle-ci.
     */
    public function outbidBy()
    {
        return $this->belongsTo(Bid::class, 'outbid_by_id');
    }

    /**
     * Vérifie si l'offre est actuellement la plus élevée.
     *
     * @return bool
     */
    public function isHighestBid()
    {
        return ! $this->outbid && $this->status === BidStatusEnum::ACTIVE;
    }

    /**
     * Vérifie si l'offre est la gagnante de l'enchère.
     *
     * @return bool
     */
    public function isWinner()
    {
        return $this->status === BidStatusEnum::WINNER;
    }

    /**
     * Obtenir le prochain montant d'enchère basé sur l'enchère automatique.
     *
     * @param  float  $currentBid
     * @return float|null
     */
    public function getNextAutoBidAmount($currentBid)
    {
        if (! $this->auto_bid || $currentBid >= $this->max_amount) {
            return null;
        }

        $auction = $this->auction;
        $nextAmount = $currentBid + $auction->increment_amount;

        return min($nextAmount, $this->max_amount);
    }

    /**
     * Marquer cette offre comme surenchérie.
     *
     * @param  int  $outbidByBidId
     * @return void
     */
    public function markAsOutbid($outbidByBidId)
    {
        $this->outbid = true;
        $this->outbid_by_id = $outbidByBidId;
        $this->status = BidStatusEnum::OUTBID;
        $this->save();
    }

    /**
     * Scope pour les offres actives.
     */
    public function scopeActive($query)
    {
        return $query->where('status', BidStatusEnum::ACTIVE)
            ->where('outbid', false);
    }

    /**
     * Scope pour les offres d'un utilisateur.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les offres gagnantes.
     */
    public function scopeWinners($query)
    {
        return $query->where('status', BidStatusEnum::WINNER);
    }

    /**
     * Scope pour les offres avec enchère automatique.
     */
    public function scopeAutoBids($query)
    {
        return $query->where('auto_bid', true);
    }
}
