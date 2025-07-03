<?php

namespace IJIDeals\VirtualCoin\Models; // Corrected Namespace

use Exception;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Added DB for transaction
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Added Exception
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="VirtualCoin",
 *     title="VirtualCoin",
 *     description="Modèle représentant le solde de la monnaie virtuelle d'un utilisateur",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de l'enregistrement de la monnaie virtuelle"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur auquel le solde de monnaie virtuelle est associé"
 *     ),
 *     @OA\Property(
 *         property="balance",
 *         type="number",
 *         format="float",
 *         description="Solde actuel de la monnaie virtuelle de l'utilisateur"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'enregistrement de la monnaie virtuelle"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'enregistrement de la monnaie virtuelle"
 *     )
 * )
 */
class VirtualCoin extends Model
{
    use HasFactory; // Added HasFactory

    protected $table = 'virtual_coins';

    protected $fillable = [
        'user_id',
        'balance',
        'currency_code',
    ];

    protected $casts = [
        'balance' => 'decimal:4', // Match migration precision
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        // Assumes CoinTransaction has virtual_coin_id
        return $this->hasMany(CoinTransaction::class, 'virtual_coin_id');
    }

    /**
     * Create a new transaction for this wallet.
     * Ensures atomicity of balance update and transaction logging.
     *
     * @param  float  $amount  Amount of the transaction (positive for credit, negative for debit).
     * @param  string  $type  Type of the transaction.
     * @param  array  $metadata  Additional metadata.
     * @param  string|null  $description  Description of the transaction.
     * @param  string|null  $reference  Optional unique reference for the transaction.
     * @param  string  $status  Initial status of the transaction.
     *
     * @throws Exception
     */
    public function createTransaction(
        float $amount,
        string $type,
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null,
        string $status = 'completed' // Default to completed, can be 'pending'
    ): CoinTransaction {
        return DB::transaction(function () use ($amount, $type, $metadata, $description, $reference, $status) {
            $balanceBefore = $this->balance;
            $newBalance = $balanceBefore + $amount;

            if ($newBalance < 0) {
                throw new Exception('Insufficient balance for this transaction.');
            }

            // Create the transaction record
            $transaction = $this->transactions()->create([
                'amount' => $amount,
                'type' => $type,
                'status' => $status, // Use provided status
                'reference' => $reference ?: Str::uuid()->toString(),
                'description' => $description,
                'metadata' => $metadata,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance, // Set balance_after based on calculation
            ]);

            // Update wallet balance
            $this->balance = $newBalance;
            $this->save();

            // If status was pending and needs to be completed by an external event,
            // this logic would be different. For now, direct completion or pending status is fine.

            return $transaction;
        });
    }

    // Removed old deposit, withdraw, spend, addBonus methods as createTransaction is now central.
    // The HasVirtualWallet trait on User model will provide user-friendly methods.

    public function getFormattedBalance(): string
    {
        return number_format($this->balance, 2).' coins';
    }

    public function getTransactionHistory(int $limit = 10)
    {
        return $this->transactions()
            ->latest()
            ->limit($limit)
            ->get();
    }
}
