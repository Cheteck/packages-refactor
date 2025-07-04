<?php

namespace IJIDeals\Inventory\Providers;

use IJIDeals\Inventory\Events\LowStockAlert;
use IJIDeals\Inventory\Listeners\SendLowStockNotification;
use IJIDeals\Inventory\Services\InventoryService; // Added import for InventoryService
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route; // Added import for Route

class InventoryServiceProvider extends ServiceProvider
{
    protected $listen = [
        LowStockAlert::class => [
            SendLowStockNotification::class,
        ],
    ];

    /**
     * The namespace for the package controllers.
     *
     * @var string|null
     */
    protected $controllerNamespace = 'IJIDeals\\Inventory\\Http\\Controllers\\Api';

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/inventory.php', 'inventory'
        );

        // Register InventoryService
        $this->app->singleton(InventoryService::class, function ($app) {
            // Add dependencies for InventoryService if any, e.g.:
            // return new InventoryService($app->make(AnotherDependency::class));
            return new InventoryService();
        });
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/inventory.php' => config_path('inventory.php'),
            ], 'config');
        }

        $this->registerEventListeners();
        $this->registerRoutes(); // Added call to register routes
    }

    protected function registerEventListeners(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * Register the API routes for the package.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        if (file_exists(__DIR__.'/../../routes/api.php')) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
        }
    }

    /**
     * Get the API route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('inventory.api_prefix', 'v1/inventory'), // Corrected prefix
            'middleware' => config('inventory.api_middleware', ['api', 'auth:sanctum']),
            // 'namespace' => $this->controllerNamespace,
        ];
    }
}
