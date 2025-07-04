<?php

namespace IJIDeals\Analytics\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route; // Added import

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * The namespace for the package controllers.
     * This assumes your API controllers will be in Http/Controllers/Api directory.
     *
     * @var string|null
     */
    protected $controllerNamespace = 'IJIDeals\\Analytics\\Http\\Controllers\\Api';

    public function register()
    {
        $this->app->singleton('analytics', function ($app) {
            // Assuming AnalyticsService is in the correct namespace
            return new \IJIDeals\Analytics\Services\AnalyticsService();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/analytics.php', 'analytics'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/analytics.php' => config_path('analytics.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations');
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
    protected function routeConfiguration()
    {
        return [
            'prefix' => config('analytics.api_prefix', 'v1/analytics'), // Corrected prefix
            // Analytics data should typically be protected.
            // Using 'auth:sanctum' as a sensible default, can be overridden by config.
            'middleware' => config('analytics.api_middleware', ['api', 'auth:sanctum']),
            // 'namespace' => $this->controllerNamespace,
        ];
    }
}
