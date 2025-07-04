<?php

namespace IJIDeals\VirtualCoin\Events;

use IJIDeals\VirtualCoin\Models\CoinTransaction;
use IJIDeals\UserManagement\Models\User; // Or your configured user model
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $fromUser;
    public User $toUser;
    public float $amount;
    public CoinTransaction $senderTransaction;
    public CoinTransaction $receiverTransaction;
    public ?string $reference;

    /**
     * Create a new event instance.
     *
     * @param User $fromUser
     * @param User $toUser
     * @param float $amount
     * @param CoinTransaction $senderTransaction
     * @param CoinTransaction $receiverTransaction
     * @param string|null $reference
     */
    public function __construct(
        User $fromUser,
        User $toUser,
        float $amount,
        CoinTransaction $senderTransaction,
        CoinTransaction $receiverTransaction,
        ?string $reference
    ) {
        $this->fromUser = $fromUser;
        $this->toUser = $toUser;
        $this->amount = $amount;
        $this->senderTransaction = $senderTransaction;
        $this->receiverTransaction = $receiverTransaction;
        $this->reference = $reference;
    }
}
