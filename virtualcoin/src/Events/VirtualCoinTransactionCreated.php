<?php

namespace IJIDeals\VirtualCoin\Events;

use IJIDeals\VirtualCoin\Models\CoinTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VirtualCoinTransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CoinTransaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param CoinTransaction $transaction
     */
    public function __construct(CoinTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
