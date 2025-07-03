<?php

namespace IJIDeals\Inventory\Providers;

use IJIDeals\Inventory\Events\LowStockAlert;
use IJIDeals\Inventory\Listeners\SendLowStockNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    protected $listen = [
        LowStockAlert::class => [
            SendLowStockNotification::class,
        ],
    ];

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/inventory.php', 'inventory'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $this->publishes([
            __DIR__.'/../Config/inventory.php' => config_path('inventory.php'),
        ], 'inventory-config');

        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
