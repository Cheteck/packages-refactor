<?php

namespace IJIDeals\ReturnsManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IJIDeals\ReturnsManagement\Models\ReturnRequest;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public $returnRequest;
    public $amount;

    /**
     * Create a new event instance.
     *
     * @param ReturnRequest $returnRequest
     * @param float $amount
     * @return void
     */
    public function __construct(ReturnRequest $returnRequest, float $amount)
    {
        $this->returnRequest = $returnRequest;
        $this->amount = $amount;
    }
}
