<?php

namespace IJIDeals\VirtualCoin\Models; // Corrected namespace casing

use IJIDeals\UserManagement\Models\User;
use IJIDeals\VirtualCoin\Traits\HasCoinTransactions; // Corrected namespace casing
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="CoinTransaction",
 *     title="CoinTransaction",
 *     description="Modèle représentant une transaction de monnaie virtuelle",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la transaction"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur concerné par la transaction"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Montant de la transaction (positif pour un dépôt, négatif pour un retrait/dépense)"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Type de transaction (ex: 'deposit', 'withdrawal', 'spend', 'refund', 'bonus')",
 *         enum={"deposit", "withdrawal", "spend", "refund", "bonus"}
 *     ),
 *     @OA\Property(
 *         property="reference",
 *         type="string",
 *         nullable=true,
 *         description="Référence unique de la transaction"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description de la transaction"
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         nullable=true,
 *         description="Données additionnelles de la transaction (JSON)"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Statut de la transaction (ex: 'pending', 'completed', 'failed', 'refunded')",
 *         enum={"pending", "completed", "failed", "refunded"}
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la transaction"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la transaction"
 *     )
 * )
 */
class CoinTransaction extends Model
{
    use HasCoinTransactions;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reference',
        'description',
        'metadata',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedAmount(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';

        return $prefix.number_format($this->amount, 2).' coins';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'deposit' => 'Dépôt',
            'withdrawal' => 'Retrait',
            'spend' => 'Dépense',
            'refund' => 'Remboursement',
            'bonus' => 'Bonus',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'blue',
            default => 'gray',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'deposit', 'bonus' => 'green',
            'withdrawal', 'spend' => 'red',
            'refund' => 'blue',
            default => 'gray',
        };
    }
}
