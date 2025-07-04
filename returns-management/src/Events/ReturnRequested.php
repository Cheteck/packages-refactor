<?php

namespace IJIDeals\ReturnsManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IJIDeals\ReturnsManagement\Models\ReturnRequest;

class ReturnRequested
{
    use Dispatchable, SerializesModels;

    public $returnRequest;

    /**
     * Create a new event instance.
     *
     * @param ReturnRequest $returnRequest
     * @return void
     */
    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
    }
}
