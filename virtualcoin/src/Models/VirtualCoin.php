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
        string $type, // Should ideally be TransactionType enum instance or its ->value
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null,
        string $status = 'completed' // Default to completed, can be 'pending' - consider using config('virtualcoin.default_transaction_status')
    ): CoinTransaction {
        // Validate transaction type using the Enum
        if (!\IJIDeals\VirtualCoin\Enums\TransactionType::isValid($type)) {
            throw new \InvalidArgumentException("Invalid transaction type: {$type}");
        }

        // Use default status from config if not overridden
        $finalStatus = $status ?: config('virtualcoin.default_transaction_status', 'completed');

        return DB::transaction(function () use ($amount, $type, $metadata, $description, $reference, $finalStatus) {
            // Idempotency Check
            if ($reference && config('virtualcoin.idempotency_enabled', false)) {
                $existingTransaction = $this->transactions()
                    ->where('reference', $reference)
                    ->where('created_at', '>=', now()->subMinutes(config('virtualcoin.idempotency_window_minutes', 1440)))
                    ->first();

                if ($existingTransaction) {
                    // If a similar transaction (same reference, type, amount) exists and is completed, return it.
                    // If it's pending or different, it might be an error or a retry of a failed one.
                    // For simplicity, we'll prevent new transaction if reference is found in window.
                    // More complex logic can be added here (e.g., check status, amount, type).
                    // For now, any transaction with the same reference in the window is considered a duplicate.
                    throw new \IJIDeals\VirtualCoin\Exceptions\DuplicateTransactionException("Duplicate transaction reference: {$reference}");
                }
            }

            $wallet = self::lockForUpdate()->find($this->id);
            if (!$wallet) {
                throw new Exception('Wallet not found or could not be locked.');
            }

            $scale = config('virtualcoin.bcmath_scale', 4);
            $balanceBefore = $wallet->balance; // Eloquent will cast this based on $casts

            // Ensure amounts are strings for BCMath
            $amountStr = (string) $amount;
            $balanceBeforeStr = (string) $balanceBefore;

            $newBalance = bcadd($balanceBeforeStr, $amountStr, $scale);

            if (bccomp($newBalance, '0', $scale) < 0) {
                throw new \IJIDeals\VirtualCoin\Exceptions\InsufficientBalanceException('Insufficient balance for this transaction.');
            }

            $transaction = $wallet->transactions()->create([
                'amount' => $amount, // Stored as per its own cast (decimal:2)
                'type' => $type,
                'status' => $finalStatus,
                'reference' => $reference ?: Str::uuid()->toString(),
                'description' => $description,
                'metadata' => $metadata,
                'balance_before' => $balanceBefore, // Stored as per wallet cast (decimal:4)
                'balance_after' => $newBalance,   // Stored as per wallet cast (decimal:4)
            ]);

            // Update wallet balance
            $wallet->balance = $newBalance; // Assign the string result from bcadd, Eloquent handles casting to DB
            $wallet->save();

            // Dispatch an event
            event(new \IJIDeals\VirtualCoin\Events\VirtualCoinTransactionCreated($transaction));

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
