<?php

namespace IJIDeals\VirtualCoin\Events;

use IJIDeals\VirtualCoin\Models\VirtualCoin;
use IJIDeals\VirtualCoin\Models\CoinTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BalanceAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public VirtualCoin $wallet;
    public CoinTransaction $transaction;
    public float $adjustedAmount; // The actual amount credited or debited
    public string $reason;
    public ?int $adminUserId;

    /**
     * Create a new event instance.
     *
     * @param VirtualCoin $wallet
     * @param CoinTransaction $transaction The transaction that recorded the adjustment
     * @param float $adjustedAmount
     * @param string $reason
     * @param int|null $adminUserId
     */
    public function __construct(
        VirtualCoin $wallet,
        CoinTransaction $transaction,
        float $adjustedAmount,
        string $reason,
        ?int $adminUserId
    ) {
        $this->wallet = $wallet;
        $this->transaction = $transaction;
        $this->adjustedAmount = $adjustedAmount;
        $this->reason = $reason;
        $this->adminUserId = $adminUserId;
    }
}
