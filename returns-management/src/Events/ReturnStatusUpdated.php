<?php

namespace IJIDeals\ReturnsManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IJIDeals\ReturnsManagement\Models\ReturnRequest;

class ReturnStatusUpdated
{
    use Dispatchable, SerializesModels;

    public $returnRequest;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new event instance.
     *
     * @param ReturnRequest $returnRequest
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public function __construct(ReturnRequest $returnRequest, string $oldStatus, string $newStatus)
    {
        $this->returnRequest = $returnRequest;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
