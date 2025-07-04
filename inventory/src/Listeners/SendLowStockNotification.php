<?php

namespace IJIDeals\Inventory\Listeners;

use IJIDeals\Inventory\Events\LowStockAlert;
use Illuminate\Support\Facades\Log;
use IJIDeals\Analytics\Facades\Analytics;

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

        Analytics::track('low_stock_alert', [
            'stockable_id' => $event->inventory->stockable_id,
            'stockable_type' => $event->inventory->stockable_type,
            'location_id' => $event->inventory->location_id,
            'current_quantity' => $event->inventory->quantity,
            'available_quantity' => $event->inventory->available_quantity,
        ]);
    }
}
