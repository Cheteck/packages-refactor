<?php

namespace IJIDeals\Sponsorship\Models;

use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SponsoredPost",
 *     title="SponsoredPost",
 *     description="Modèle représentant un post sponsorisé",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui sponsorise le post"
 *     ),
 *     @OA\Property(
 *         property="post_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du post social sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         nullable=true,
 *         description="Titre de la campagne de parrainage"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description de la campagne de parrainage"
 *     ),
 *     @OA\Property(
 *         property="budget",
 *         type="number",
 *         format="float",
 *         description="Budget total alloué à la campagne de parrainage"
 *     ),
 *     @OA\Property(
 *         property="cost_per_impression",
 *         type="number",
 *         format="float",
 *         description="Coût par impression pour le post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="cost_per_click",
 *         type="number",
 *         format="float",
 *         description="Coût par clic pour le post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="targeting",
 *         type="object",
 *         nullable=true,
 *         description="Critères de ciblage pour le post sponsorisé (JSON)"
 *     ),
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date de début de la campagne de parrainage"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date de fin de la campagne de parrainage"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Statut de la campagne de parrainage",
 *         enum={"pending", "active", "paused", "completed", "cancelled", "exhausted_budget"}
 *     ),
 *     @OA\Property(
 *         property="impressions",
 *         type="integer",
 *         description="Nombre d'impressions du post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="clicks",
 *         type="integer",
 *         description="Nombre de clics sur le post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="spent_amount",
 *         type="number",
 *         format="float",
 *         description="Montant total dépensé pour la campagne"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'enregistrement du post sponsorisé"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'enregistrement du post sponsorisé"
 *     )
 * )
 */
class SponsoredPost extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $user_id;

    /** @var int */
    public $post_id;

    /** @var string */
    public $title;

    /** @var string|null */
    public $description;

    /** @var float|string */
    public $budget;

    /** @var float|string */
    public $cost_per_impression;

    /** @var float|string */
    public $cost_per_click;

    /** @var array|null */
    public $targeting;

    /** @var string|null|\Carbon\Carbon */
    public $start_date;

    /** @var string|null|\Carbon\Carbon */
    public $end_date;

    /** @var string */
    public $status;

    /** @var int */
    public $impressions;

    /** @var int */
    public $clicks;

    /** @var float|string */
    public $spent_amount;

    /**
     * @var User|null
     *
     * @property \IJIDeals\VirtualCoin\Models\VirtualCoinAccount|null $virtualCoin
     */
    public $user; // For relationship access

    /** @var Post|null */
    public $post; // For relationship access

    protected $fillable = [
        'user_id',
        'post_id',
        'title',
        'description',
        'budget',
        'cost_per_impression',
        'cost_per_click',
        'targeting',
        'start_date',
        'end_date',
        'status',
        'impressions',
        'clicks',
        'spent_amount',
    ];

    protected $casts = [
        'targeting' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'budget' => 'decimal:2',
        'cost_per_impression' => 'decimal:4',
        'cost_per_click' => 'decimal:4',
        'spent_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(\IJIDeals\VirtualCoin\Models\CoinTransaction::class, 'metadata->sponsored_post_id'); // Corrected case in type hint usage
    }

    public function calculateRemainingBudget(): float
    {
        return $this->budget - $this->spent_amount;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->start_date, $this->end_date) &&
               $this->calculateRemainingBudget() > 0;
    }

    public function recordImpression(): void
    {
        if (! $this->isActive()) {
            return;
        }

        DB::transaction(function () {
            $this->increment('impressions');
            $this->increment('spent_amount', $this->cost_per_impression);

            // Record transaction
            // Ensure user and virtualCoin relationship are loaded and exist
            if ($this->user && $this->user->virtualCoin) {
                $this->user->virtualCoin->createTransaction(
                    -(float) $this->cost_per_impression,
                    'spend_sponsorship_impression', // More specific type
                    ['sponsored_post_id' => $this->id, 'post_id' => $this->post_id],
                    "Impression for sponsored post #{$this->id}",
                    'sp_imp_'.uniqid() // Unique reference
                );
            } else {
                // Log error or throw exception if user or virtual coin account is missing
                Log::error("User or virtual coin account not found for sponsored post: {$this->id} during impression recording.");
                // Optionally throw an exception to roll back if this is critical
                // throw new \Exception("User or virtual coin account not found.");
            }
        });
    }

    public function recordClick(): void
    {
        if (! $this->isActive()) {
            return;
        }

        DB::transaction(function () {
            $this->increment('clicks');
            $this->increment('spent_amount', $this->cost_per_click);

            // Record transaction
            // Ensure user and virtualCoin relationship are loaded and exist
            if ($this->user && $this->user->virtualCoin) {
                $this->user->virtualCoin->createTransaction(
                    -(float) $this->cost_per_click,
                    'spend_sponsorship_click', // More specific type
                    ['sponsored_post_id' => $this->id, 'post_id' => $this->post_id],
                    "Click for sponsored post #{$this->id}",
                    'sp_clk_'.uniqid() // Unique reference
                );
            } else {
                // Log error or throw exception if user or virtual coin account is missing
                Log::error("User or virtual coin account not found for sponsored post: {$this->id} during click recording.");
                // Optionally throw an exception
                // throw new \Exception("User or virtual coin account not found.");
            }
        });
    }
}
