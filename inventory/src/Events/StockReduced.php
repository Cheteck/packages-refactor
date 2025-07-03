<?php

namespace IJIDeals\Inventory\Events;

use IJIDeals\Inventory\Models\StockMovement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockReduced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StockMovement $stockMovement;

    /**
     * Create a new event instance.
     */
    public function __construct(StockMovement $stockMovement)
    {
        $this->stockMovement = $stockMovement;
    }
}
