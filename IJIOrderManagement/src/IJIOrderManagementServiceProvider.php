<?php

namespace IJIDeals\IJIOrderManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class IJIOrderManagementServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the package.
     * @var array
     */
    protected $policies = [
        \IJIDeals\IJIOrderManagement\Models\Order::class => \IJIDeals\IJIOrderManagement\Policies\OrderPolicy::class,
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
        Log::info('IJIOrderManagementServiceProvider: Booting package.');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ijiordermanagement.php' => config_path('ijiordermanagement.php'),
            ], ['ijiordermanagement-config', 'config']);

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations/vendor/ijideals/ijiordermanagement'),
            ], ['ijiordermanagement-migrations', 'migrations']);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
        $this->registerPolicies();
        Log::info('IJIOrderManagementServiceProvider: Package booted.');
    }

    public function register()
    {
        Log::info('IJIOrderManagementServiceProvider: Registering package.');
        $this->mergeConfigFrom(
            __DIR__.'/../config/ijiordermanagement.php', 'ijiordermanagement'
        );

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
        Log::info('IJIOrderManagementServiceProvider: Package registered.');
    }

    protected function registerRoutes()
    {
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $prefix = config('ijiordermanagement.routes.prefix', 'api/orders');
            $middleware = config('ijiordermanagement.routes.middleware', ['api']);

            \Illuminate\Support\Facades\Route::prefix($prefix)
                 ->middleware($middleware)
                 ->name('ijiordermanagement.api.')
                 ->group(__DIR__.'/../routes/api.php');
            Log::info('IJIOrderManagementServiceProvider: API routes registered.', ['prefix' => $prefix, 'middleware' => $middleware]);
        }
    }

    public function registerPolicies()
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
            Log::debug('IJIOrderManagementServiceProvider: Registered policy.', ['model' => $model, 'policy' => $policy]);
        }
        Log::info('IJIOrderManagementServiceProvider: Policies registered.');
    }
}
