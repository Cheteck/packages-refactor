<?php

namespace IJIDeals\AuctionSystem\Providers;

use IJIDeals\AuctionSystem\Jobs\DetermineAuctionWinnerJob;
use IJIDeals\AuctionSystem\Services\AuctionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use IJIDeals\AuctionSystem\Events\NewBidPlaced;
use IJIDeals\AuctionSystem\Listeners\SendOverbidNotification;
use Illuminate\Support\Facades\Route; // Added import

class AuctionSystemServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        NewBidPlaced::class => [
            SendOverbidNotification::class,
        ],
        \IJIDeals\AuctionSystem\Events\AuctionEnded::class => [
            \IJIDeals\AuctionSystem\Listeners\SendAuctionEndedNotification::class,
        ],
    ];

    /**
     * The namespace for the package controllers.
     *
     * @var string|null
     */
    protected $controllerNamespace = 'IJIDeals\\AuctionSystem\\Http\\Controllers';


    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/auction-system.php', 'auction-system'
        );

        $this->app->singleton(AuctionService::class, function ($app) {
            return new AuctionService;
        });
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/auction-system.php' => config_path('auction-system.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'migrations');

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->job(new DetermineAuctionWinnerJob)->everyMinute();
                // TODO: Make frequency configurable
            });
        }

        $this->registerRoutes();
    }

    /**
     * Register the API routes for the package.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });
    }

    /**
     * Get the Nova route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            // It's good practice to make prefix and middleware configurable,
            // but for plug & play, we can provide sensible defaults.
            // Laravel's Route::middleware('api') group already adds 'api/' prefix.
            'prefix' => config('auction-system.api_prefix', 'v1/auction-system'), // Corrected prefix
            'middleware' => config('auction-system.api_middleware', ['api']), // 'api' middleware group is usually sufficient
            // If specific auth is needed for all routes, add it here or per route.
            // e.g. 'middleware' => config('auction-system.api_middleware', ['api', 'auth:sanctum']),
            // For this package, 'auth:sanctum' was applied per-route for bids.
            // The main 'api' group often includes throttling.
            // 'namespace' => $this->controllerNamespace . '\\Api',
        ];
    }
}
