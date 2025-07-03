<?php

namespace IJIDeals\VirtualCoin\Traits;

use Exception;
use IJIDeals\VirtualCoin\Models\CoinTransaction;
use IJIDeals\VirtualCoin\Models\VirtualCoin;
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // For transactions through wallet
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasVirtualWallet
{
    /**
     * Get the user's virtual coin wallet.
     */
    public function virtualCoin(): HasOne
    {
        return $this->hasOne(VirtualCoin::class, 'user_id');
    }

    /**
     * Get or create the user's virtual coin wallet.
     */
    public function getWallet(): VirtualCoin
    {
        if (! $this->virtualCoin) {
            $this->virtualCoin()->create([
                'balance' => 0,
                'currency_code' => config('virtualcoin.default_currency_code', 'VC'),
            ]);
            $this->load('virtualCoin'); // Reload the relationship
        }

        return $this->virtualCoin;
    }

    /**
     * Get the user's virtual coin balance.
     */
    public function getCoinBalance(): float
    {
        return (float) $this->getWallet()->balance;
    }

    /**
     * Deposit virtual coins into the user's wallet.
     *
     * @param  float  $amount  The amount to deposit (must be positive).
     * @param  string  $type  The type of transaction (e.g., 'deposit_purchase', 'reward_bonus').
     * @param  array  $metadata  Optional metadata for the transaction.
     * @param  string|null  $description  Optional description.
     * @param  string|null  $reference  Optional unique reference for idempotency.
     *
     * @throws Exception
     */
    public function depositCoins(
        float $amount,
        string $type = 'deposit',
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null
    ): CoinTransaction {
        if ($amount <= 0) {
            throw new Exception('Deposit amount must be positive.');
        }

        return $this->getWallet()->createTransaction($amount, $type, $metadata, $description, $reference, 'completed');
    }

    /**
     * Withdraw/spend virtual coins from the user's wallet.
     *
     * @param  float  $amount  The amount to withdraw (must be positive).
     * @param  string  $type  The type of transaction (e.g., 'spend_item', 'withdrawal_cash_out').
     * @param  array  $metadata  Optional metadata.
     * @param  string|null  $description  Optional description.
     * @param  string|null  $reference  Optional unique reference.
     *
     * @throws Exception
     */
    public function withdrawCoins(
        float $amount,
        string $type = 'spend',
        array $metadata = [],
        ?string $description = null,
        ?string $reference = null
    ): CoinTransaction {
        if ($amount <= 0) {
            throw new Exception('Withdrawal amount must be positive.');
        }

        // The createTransaction method in VirtualCoin model handles balance check.
        return $this->getWallet()->createTransaction(-$amount, $type, $metadata, $description, $reference, 'completed');
    }

    /**
     * Check if the user has sufficient balance.
     */
    public function hasSufficientCoinBalance(float $amount): bool
    {
        if ($amount < 0) {
            $amount = 0;
        } // Cannot check for negative balance requirement

        return $this->getCoinBalance() >= $amount;
    }

    /**
     * Get all coin transactions for the user through their wallet.
     * Note: This assumes CoinTransaction has a virtual_coin_id.
     */
    // public function coinTransactions(): HasManyThrough
    // {
    //     // This relationship might be complex or better fetched directly:
    //     // CoinTransaction::where('virtual_coin_id', $this->getWallet()->id)->orderBy('created_at', 'desc');
    //     // For HasManyThrough, you'd need an intermediate model or a direct HasMany on VirtualCoin model.
    //     // Let's assume VirtualCoin model has a direct 'transactions' HasMany relationship.
    //     // This is more of a shortcut and might be better handled by calling $this->getWallet()->transactions()
    //     return $this->hasManyThrough(
    //         CoinTransaction::class,
    //         VirtualCoin::class,
    //         'user_id', // Foreign key on VirtualCoin table
    //         'virtual_coin_id', // Foreign key on CoinTransaction table
    //         'id', // Local key on User table
    //         'id' // Local key on VirtualCoin table
    //     );
    // }
    // Simpler: just get from the wallet instance
    public function getCoinTransactions()
    {
        return $this->getWallet()->transactions()->orderBy('created_at', 'desc')->get();
    }
}
