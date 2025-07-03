<?php

namespace IJIDeals\IJICommerce;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Policies\ShopPolicy;
use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Policies\ShopProductPolicy;
use IJIDeals\IJICommerce\Models\Order;
use IJIDeals\IJICommerce\Policies\OrderPolicy;
use Illuminate\Support\Facades\Log;

class IJICommerceServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the package.
     *
     * @var array
     */
    protected $policies = [
        Shop::class => ShopPolicy::class,
        ShopProduct::class => ShopProductPolicy::class,
    ];

    /**
     * The Artisan commands provided by the package.
     *
     * @var array
     */
    protected $commands = [
        \IJIDeals\IJICommerce\Commands\InstallIJICommerceCommand::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Log::info('IJICommerceServiceProvider: Booting package.');
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/ijicommerce.php' => config_path('ijicommerce.php'),
        ], ['ijicommerce-config', 'config']);

        // Load migrations
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations/vendor/ijideals/ijicommerce'),
            ], ['ijicommerce-migrations', 'migrations']);

            // Publish Seeders
            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders/vendor/ijideals/ijicommerce'),
            ], ['ijicommerce-seeders', 'seeders']);
        }

        $this->registerRoutes();
        $this->registerPolicies(); // Ensure policies are registered
        Log::info('IJICommerceServiceProvider: Package booted.');
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        // API routes
        if (file_exists(__DIR__.'/../routes/api.php')) {
            // Get prefix from package config, default to 'api/ijicommerce'
            $prefix = config('ijicommerce.route_prefix', 'api/ijicommerce');
            $middleware = config('ijicommerce.route_middleware', ['api']); // Default to 'api' middleware group

            // Ensure \Illuminate\Support\Facades\Route is used
            \Illuminate\Support\Facades\Route::prefix($prefix)
                 ->middleware($middleware)
                 ->name('ijicommerce.api.')
                 ->group(__DIR__.'/../routes/api.php');
            Log::info('IJICommerceServiceProvider: API routes registered.', ['prefix' => $prefix, 'middleware' => $middleware]);
        }
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
            Log::debug('IJICommerceServiceProvider: Registered policy.', ['model' => $model, 'policy' => $policy]);
        }
        Log::info('IJICommerceServiceProvider: Policies registered.');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Log::info('IJICommerceServiceProvider: Registering package.');
        $this->mergeConfigFrom(
            __DIR__.'/../config/ijicommerce.php', 'ijicommerce'
        );

        // Register package services or bindings here

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
        Log::info('IJICommerceServiceProvider: Package registered.');
    }
}