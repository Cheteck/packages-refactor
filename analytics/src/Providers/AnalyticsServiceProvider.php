<?php

namespace IJIDeals\Analytics\Providers;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/analytics.php', 'analytics'
        );
    }

    public function boot()
    {
        // Load migrations from the conventional src/Database/migrations path
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/analytics.php' => config_path('analytics.php'),
            ], 'config'); // Use standard 'config' tag

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations'); // Use standard 'migrations' tag
        }
    }
}
