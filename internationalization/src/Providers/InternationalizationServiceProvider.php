<?php

namespace IJIDeals\Internationalization\Providers;

use Illuminate\Support\ServiceProvider;

class InternationalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__.'/../Config/internationalization.php',
            'internationalization'
        );
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../Config/internationalization.php' => config_path('internationalization.php'),
        ], 'config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'internationalization');

        // Register helpers (autoloaded via composer, but can be required here if needed)

        // Register API routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
