<?php

namespace IJIDeals\VirtualCoin\Services;

use IJIDeals\UserManagement\Models\User; // Or the configured user model
use IJIDeals\VirtualCoin\Enums\TransactionType;
use IJIDeals\VirtualCoin\Exceptions\InsufficientBalanceException;
use IJIDeals\VirtualCoin\Exceptions\DuplicateTransactionException;
use IJIDeals\VirtualCoin\Models\VirtualCoin;
use IJIDeals\VirtualCoin\Models\CoinTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log; // For logging admin adjustments

class WalletService
{
    protected string $userModel;

    public function __construct()
    {
        $this->userModel = config('virtualcoin.user_model', \App\Models\User::class);
    }

    /**
     * Get or create a wallet for a given user.
     *
     * @param int|User $userIdOrModel User ID or User model instance.
     * @return VirtualCoin
     */
    public function getOrCreateWallet($userIdOrModel): VirtualCoin
    {
        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);

        // The getWallet method on the HasVirtualWallet trait handles creation if not exists.
        return $user->getWallet();
    }

    /**
     * Get the balance for a given user's wallet.
     *
     * @param int|User $userIdOrModel
     * @return float
     */
    public function getBalance($userIdOrModel): string
    {
        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);
        // getCoinBalance from trait now returns string
        return $user->getCoinBalance();
    }

    /**
     * Deposit coins into a user's wallet.
     *
     * @param int|User $userIdOrModel
     * @param float $amount
     * @param TransactionType|string $type
     * @param array $metadata
     * @param string|null $description
     * @param string|null $reference
     * @return CoinTransaction
     * @throws \InvalidArgumentException If amount is not positive or type is invalid.
     * @throws DuplicateTransactionException If reference is duplicated within idempotency window.
     */
    public function deposit(
        $userIdOrModel,
        float $amount,
        $type,
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null
    ): CoinTransaction {
        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);
        $transactionType = $type instanceof TransactionType ? $type->value : $type;

        // Basic validation for type could be done here too, or rely on model's createTransaction
        if (!TransactionType::isValid($transactionType)) {
             throw new \InvalidArgumentException("Invalid transaction type: {$transactionType}");
        }

        return $user->depositCoins($amount, $transactionType, $metadata, $description, $reference);
    }

    /**
     * Withdraw coins from a user's wallet.
     *
     * @param int|User $userIdOrModel
     * @param float $amount
     * @param TransactionType|string $type
     * @param array $metadata
     * @param string|null $description
     * @param string|null $reference
     * @return CoinTransaction
     * @throws \InvalidArgumentException If amount is not positive or type is invalid.
     * @throws InsufficientBalanceException If balance is not enough.
     * @throws DuplicateTransactionException If reference is duplicated within idempotency window.
     */
    public function withdraw(
        $userIdOrModel,
        float $amount,
        $type,
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null
    ): CoinTransaction {
        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);
        $transactionType = $type instanceof TransactionType ? $type->value : $type;

        if (!TransactionType::isValid($transactionType)) {
             throw new \InvalidArgumentException("Invalid transaction type: {$transactionType}");
        }

        return $user->withdrawCoins($amount, $transactionType, $metadata, $description, $reference);
    }

    /**
     * Transfer coins from one user's wallet to another.
     * This requires careful implementation regarding authorization and atomicity.
     *
     * @param int|User $fromUserIdOrModel
     * @param int|User $toUserIdOrModel
     * @param float $amount
     * @param string|null $descriptionForSender
     * @param string|null $descriptionForReceiver
     * @param array $metadataForSender
     * @param array $metadataForReceiver
     * @param string|null $reference (A single reference for the entire transfer operation)
     * @return array ['sender_transaction' => CoinTransaction, 'receiver_transaction' => CoinTransaction]
     * @throws \InvalidArgumentException
     * @throws InsufficientBalanceException
     * @throws DuplicateTransactionException
     */
    public function transfer(
        $fromUserIdOrModel,
        $toUserIdOrModel,
        float $amount,
        ?string $descriptionForSender = 'Transfer sent',
        ?string $descriptionForReceiver = 'Transfer received',
        array $metadataForSender = [],
        array $metadataForReceiver = [],
        ?string $reference = null
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }

        $fromUser = $fromUserIdOrModel instanceof $this->userModel ? $fromUserIdOrModel : $this->userModel::findOrFail($fromUserIdOrModel);
        $toUser = $toUserIdOrModel instanceof $this->userModel ? $toUserIdOrModel : $this->userModel::findOrFail($toUserIdOrModel);

        if ($fromUser->id === $toUser->id) {
            throw new \InvalidArgumentException('Cannot transfer coins to the same user.');
        }

        // It's crucial that both operations (withdrawal and deposit) are atomic together.
        // The individual withdraw/deposit methods already use DB::transaction, but for a transfer,
        // the entire operation should be in one transaction to ensure if one part fails, both are rolled back.
        // However, nested transactions can be tricky. A simpler approach for now is to rely on the atomicity
        // of each, and if the deposit fails after withdrawal, a compensating transaction would be needed.
        // A truly atomic transfer would require a more complex transaction handling here or at a lower level.

        // For now, let's assume if withdrawal is successful, deposit should also be.
        // A more robust solution might involve a two-phase commit or a saga pattern if microservices were involved,
        // but for a monolithic app, a single DB transaction around both *could* work if VirtualCoin::createTransaction
        // was refactored to not start its own DB::transaction or to support joining an existing one.

        // Let's proceed with separate calls, acknowledging this potential atomicity issue for cross-wallet transfers.
        // The idempotency check on reference should be for the whole transfer operation.
        // We can pass the same reference to both.

        $senderTransaction = $this->withdraw(
            $fromUser,
            $amount,
            TransactionType::SPEND_ITEM, // Or a new 'TRANSFER_SENT' type
            array_merge($metadataForSender, ['to_user_id' => $toUser->id]),
            $descriptionForSender,
            $reference // Use the same reference for both legs of the transfer
        );

        try {
            $receiverTransaction = $this->deposit(
                $toUser,
                $amount,
                TransactionType::DEPOSIT_BONUS, // Or a new 'TRANSFER_RECEIVED' type
                array_merge($metadataForReceiver, ['from_user_id' => $fromUser->id]),
                $descriptionForReceiver,
                $reference // Use the same reference
            );
        } catch (\Exception $e) {
            // Attempt to refund the sender if deposit fails. This is a compensating transaction.
            // This part needs robust error handling and potentially a retry mechanism or manual intervention flag.
            Log::error("Transfer failed: Deposit to user {$toUser->id} failed after withdrawal from user {$fromUser->id}. Attempting refund.", ['error' => $e->getMessage(), 'reference' => $reference]);
            try {
                $this->deposit(
                    $fromUser,
                    $amount,
                    TransactionType::REFUND_ITEM, // Or 'TRANSFER_FAILED_REFUND'
                    ['original_reference' => $reference, 'failure_reason' => 'receiver_deposit_failed'],
                    'Refund for failed transfer',
                    Str::uuid()->toString() // New reference for the refund transaction
                );
                Log::info("Transfer failure refund processed for user {$fromUser->id}.", ['reference' => $reference]);
            } catch (\Exception $refundException) {
                Log::critical("CRITICAL: Transfer failure refund FAILED for user {$fromUser->id}.", ['original_reference' => $reference, 'refund_error' => $refundException->getMessage()]);
                // At this point, manual intervention is likely required.
            }
            throw $e; // Re-throw the original exception
        }

        $transferResult = [
            'sender_transaction' => $senderTransaction,
            'receiver_transaction' => $receiverTransaction,
        ];

        event(new \IJIDeals\VirtualCoin\Events\TransferOccurred(
            $fromUser,
            $toUser,
            $amount,
            $senderTransaction,
            $receiverTransaction,
            $reference
        ));

        return $transferResult;
    }


    /**
     * Admin-only: Adjust a user's wallet balance.
     * This should be heavily protected by authorization.
     *
     * @param int|User $userIdOrModel
     * @param float $amount The amount to adjust by (can be positive or negative).
     * @param string $reason Description of why the adjustment is made.
     * @param int|null $adminUserId The ID of the admin performing the action.
     * @return CoinTransaction
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function adjustBalance(
        $userIdOrModel,
        float $amount,
        string $reason,
        ?int $adminUserId = null
    ): CoinTransaction {
        // Example of Authorization. Implement 'adjust-virtual-coin-balance' gate.
        if (Gate::denies('adjust-virtual-coin-balance', $this->getWallet($userIdOrModel))) {
             throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
        }

        if ($amount == 0) {
            throw new \InvalidArgumentException('Adjustment amount cannot be zero.');
        }

        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);
        $type = $amount > 0 ? TransactionType::ADJUSTMENT_CREDIT : TransactionType::ADJUSTMENT_DEBIT;

        $admin = $adminUserId ? $this->userModel::find($adminUserId) : Auth::user();
        $metadata = [
            'reason' => $reason,
            'admin_id' => $admin ? $admin->id : null,
            'admin_name' => $admin ? $admin->name : 'System', // Or however you get admin name
        ];

        Log::channel(config('virtualcoin.log_channel', 'stack'))->info(
            "Balance adjustment for user {$user->id}: {$amount} coins. Reason: {$reason}. Admin: " . ($admin ? $admin->id : 'System')
        );

        // For adjustments, we directly use the amount.
        // If it's a debit, amount should be negative. If credit, positive.
        $transaction = $user->getWallet()->createTransaction(
            $amount,
            $type->value,
            $metadata,
            $reason,
            null, // No external reference needed usually for admin adjustments, UUID will be generated
            config('virtualcoin.default_transaction_status', 'completed')
        );

        event(new \IJIDeals\VirtualCoin\Events\BalanceAdjusted($user->getWallet(), $transaction, $amount, $reason, $admin ? $admin->id : null));

        return $transaction;
    }

    /**
     * Get transaction history for a user.
     *
     * @param int|User $userIdOrModel
     * @param int $limit
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTransactionHistory($userIdOrModel, int $limit = 15, int $page = 1)
    {
        $user = $userIdOrModel instanceof $this->userModel ? $userIdOrModel : $this->userModel::findOrFail($userIdOrModel);
        return $user->getWallet()->transactions()->latest()->paginate($limit, ['*'], 'page', $page);
    }
}
