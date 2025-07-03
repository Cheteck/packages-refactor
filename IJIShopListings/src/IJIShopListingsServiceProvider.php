<?php

namespace IJIDeals\IJIShopListings;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class IJIShopListingsServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the package.
     * @var array
     */
    protected $policies = [
        \IJIDeals\IJIShopListings\Models\ShopProduct::class => \IJIDeals\IJIShopListings\Policies\ShopProductPolicy::class,
    ];

    /**
     * The Artisan commands provided by the package.
     * @var array
     */
    protected $commands = [
        // Commands will be added here
    ];

    public function boot()
    {
        Log::info('IJIShopListingsServiceProvider: Booting package.');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ijishoplistings.php' => config_path('ijishoplistings.php'),
            ], ['ijishoplistings-config', 'config']);

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations/vendor/ijideals/ijishoplistings'),
            ], ['ijishoplistings-migrations', 'migrations']);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
        $this->registerPolicies();
        Log::info('IJIShopListingsServiceProvider: Package booted.');
    }

    public function register()
    {
        Log::info('IJIShopListingsServiceProvider: Registering package.');
        $this->mergeConfigFrom(
            __DIR__.'/../config/ijishoplistings.php', 'ijishoplistings'
        );

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
        Log::info('IJIShopListingsServiceProvider: Package registered.');
    }

    protected function registerRoutes()
    {
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $prefix = config('ijishoplistings.routes.prefix', 'api/shop-listings');
            $middleware = config('ijishoplistings.routes.middleware', ['api']);

            \Illuminate\Support\Facades\Route::prefix($prefix)
                 ->middleware($middleware)
                 ->name('ijishoplistings.api.')
                 ->group(__DIR__.'/../routes/api.php');
            Log::info('IJIShopListingsServiceProvider: API routes registered.', ['prefix' => $prefix, 'middleware' => $middleware]);
        }
    }

    public function registerPolicies()
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
            Log::debug('IJIShopListingsServiceProvider: Registered policy.', ['model' => $model, 'policy' => $policy]);
        }
        Log::info('IJIShopListingsServiceProvider: Policies registered.');
    }
}
