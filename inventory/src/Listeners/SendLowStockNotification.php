<?php

namespace IJIDeals\Inventory\Listeners;

use IJIDeals\Inventory\Events\LowStockAlert;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(LowStockAlert $event)
    {
        Log::info('Low stock alert for product '.$event->inventory->stockable_id.' at location '.$event->inventory->location_id.'. Current quantity: '.$event->inventory->available_quantity);
    }
}
