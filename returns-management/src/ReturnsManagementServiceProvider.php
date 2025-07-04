<?php

namespace IJIDeals\ReturnsManagement;

use Illuminate\Support\ServiceProvider;

class ReturnsManagementServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \IJIDeals\ReturnsManagement\Events\ReturnRequested::class => [
            \IJIDeals\ReturnsManagement\Listeners\SendReturnNotifications::class . '@handleReturnRequested',
        ],
        \IJIDeals\ReturnsManagement\Events\ReturnStatusUpdated::class => [
            \IJIDeals\ReturnsManagement\Listeners\SendReturnNotifications::class . '@handleReturnStatusUpdated',
        ],
        \IJIDeals\ReturnsManagement\Events\RefundProcessed::class => [
            \IJIDeals\ReturnsManagement\Listeners\SendReturnNotifications::class . '@handleRefundProcessed',
        ],
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\IJIDeals\ReturnsManagement\Services\ReturnService::class, function ($app) {
            return new \IJIDeals\ReturnsManagement\Services\ReturnService(
                $app->make(\IJIDeals\Inventory\Services\InventoryService::class),
                $app->make(\IJIDeals\NotificationsManager\Services\NotificationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/returns-management.php' => config_path('returns-management.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }
    }
}
