<?php

namespace IJIDeals\IJIProductCatalog;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class IJIProductCatalogServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the package.
     * @var array
     */
    protected $policies = [
        \IJIDeals\IJIProductCatalog\Models\Brand::class => \IJIDeals\IJIProductCatalog\Policies\Admin\BrandPolicy::class,
        \IJIDeals\IJIProductCatalog\Models\Category::class => \IJIDeals\IJIProductCatalog\Policies\Admin\CategoryPolicy::class,
        \IJIDeals\IJIProductCatalog\Models\MasterProduct::class => \IJIDeals\IJIProductCatalog\Policies\Admin\MasterProductPolicy::class,
        \IJIDeals\IJIProductCatalog\Models\MasterProductVariation::class => \IJIDeals\IJIProductCatalog\Policies\Admin\MasterProductVariationPolicy::class,
        \IJIDeals\IJIProductCatalog\Models\ProductAttribute::class => \IJIDeals\IJIProductCatalog\Policies\Admin\ProductAttributePolicy::class,
        \IJIDeals\IJIProductCatalog\Models\ProductProposal::class => \IJIDeals\IJIProductCatalog\Policies\ProductProposalPolicy::class,
    ];

    /**
     * The Artisan commands provided by the package.
     * @var array
     */
    protected $commands = [
        // Example: \IJIDeals\IJIProductCatalog\Commands\SomeCommand::class,
    ];

    public function boot()
    {
        Log::info('IJIProductCatalogServiceProvider: Booting package.');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ijiproductcatalog.php' => config_path('ijiproductcatalog.php'),
            ], ['ijiproductcatalog-config', 'config']);

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations/vendor/ijideals/ijiproductcatalog'),
            ], ['ijiproductcatalog-migrations', 'migrations']);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
        $this->registerPolicies();
        Log::info('IJIProductCatalogServiceProvider: Package booted.');
    }

    public function register()
    {
        Log::info('IJIProductCatalogServiceProvider: Registering package.');
        $this->mergeConfigFrom(
            __DIR__.'/../config/ijiproductcatalog.php', 'ijiproductcatalog'
        );

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
        Log::info('IJIProductCatalogServiceProvider: Package registered.');
    }

    protected function registerRoutes()
    {
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $prefix = config('ijiproductcatalog.routes.prefix');
            $middleware = config('ijiproductcatalog.routes.middleware');

            \Illuminate\Support\Facades\Route::prefix($prefix)
                 ->middleware($middleware)
                 ->name('ijiproductcatalog.api.')
                 ->group(__DIR__.'/../routes/api.php');
            Log::info('IJIProductCatalogServiceProvider: API routes registered.', ['prefix' => $prefix, 'middleware' => $middleware]);
        }
    }

    public function registerPolicies()
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
            Log::debug('IJIProductCatalogServiceProvider: Registered policy.', ['model' => $model, 'policy' => $policy]);
        }
        Log::info('IJIProductCatalogServiceProvider: Policies registered.');
    }
}