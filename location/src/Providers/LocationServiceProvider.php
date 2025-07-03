<?php

namespace IJIDeals\Location\Providers;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/location.php', 'location'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load migrations from the conventional src/Database/migrations path
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            // Publishing migrations
            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations'); // Standard 'migrations' tag

            // Publishing configuration (if a config file is added later)
            $this->publishes([
                __DIR__.'/../../config/location.php' => config_path('location.php'),
            ], 'config');

            // Publishing seeders (if seeders are created)
            // $this->publishes([
            //     __DIR__.'/../Database/seeders/' => database_path('seeders')
            // ], 'seeds'); // A common tag for seeders
        }

        // Load routes if the package has them
        // $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        // Or web.php
    }
}
