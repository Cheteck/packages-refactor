<?php

namespace IJIDeals\VirtualCoin\Providers; // Corrected namespace

use Illuminate\Support\ServiceProvider;

// Assuming a service might be created later, or specific bindings are needed.
// use IJIDeals\VirtualCoin\Services\VirtualCoinService;

class VirtualCoinServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/virtualcoin.php', 'virtualcoin'
        );

        // Example: If you create a VirtualCoinService
        // $this->app->singleton(VirtualCoinService::class, function ($app) {
        //     return new VirtualCoinService();
        // });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! file_exists(config_path('virtualcoin.php'))) {
            $this->publishes([
                __DIR__.'/../../config/virtualcoin.php' => config_path('virtualcoin.php'),
            ], 'config');
        }

        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            // Publishing config is often done here as well, to allow users to customize after initial setup.
            // This makes it appear with `vendor:publish`
            $this->publishes([
                __DIR__.'/../../config/virtualcoin.php' => config_path('virtualcoin.php'),
            ], 'config'); // Using the generic 'config' tag is common.

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations'); // Using the generic 'migrations' tag.
        }
    }
}
