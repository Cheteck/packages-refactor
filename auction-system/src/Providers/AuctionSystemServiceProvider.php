<?php

namespace IJIDeals\AuctionSystem\Providers;

use IJIDeals\AuctionSystem\Jobs\DetermineAuctionWinnerJob;
use IJIDeals\AuctionSystem\Services\AuctionService; // Assuming service will be created
use Illuminate\Console\Scheduling\Schedule; // Assuming job will be created
use Illuminate\Support\ServiceProvider;
use IJIDeals\AuctionSystem\Events\NewBidPlaced;
use IJIDeals\AuctionSystem\Listeners\SendOverbidNotification;

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

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/auction-system.php', 'auction-system' // Corrected path if config is at root/config
        );

        $this->app->singleton(AuctionService::class, function ($app) {
            return new AuctionService;
        });
    }

    public function boot()
    {
        // The path to migrations should be relative to this file, assuming migrations are in package_root/database/migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/auction-system.php' => config_path('auction-system.php'),
            ], 'config'); // Standard tag 'config'

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'migrations'); // Standard tag 'migrations'

            // Registering scheduled jobs
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                // Schedule the job to run every minute, or as configured
                $schedule->job(new DetermineAuctionWinnerJob)->everyMinute();
                // TODO: Make frequency configurable via config('auction-system.winner_job_frequency', 'everyMinute')
            });
        }

        // Load routes if any (e.g., routes/api.php at package root)
        // $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
