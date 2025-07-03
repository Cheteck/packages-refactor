<?php

namespace IJIDeals\UserManagement;

use Illuminate\Support\ServiceProvider;

class UserManagementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/user-management.php', 'user-management'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'user-management');

        $this->publishes([
            __DIR__.'/../config/user-management.php' => config_path('user-management.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/user-management'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'migrations');

        // php artisan vendor:publish --tag=user-management-assets
        $this->publishes([
            // Example: __DIR__.'/../public' => public_path('vendor/user-management'),
        ], ['user-management-assets', 'laravel-assets']);
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        // Web routes
        if (file_exists(__DIR__.'/../routes/web.php')) {
            Route::middleware('web') // Ensure web middleware group is applied
                 ->namespace($this->namespace) // If your controllers are namespaced
                 ->group(__DIR__.'/../routes/web.php');
        }

        // API routes
        if (file_exists(__DIR__.'/../routes/api.php')) {
            Route::prefix('api') // Standard API prefix
                 ->middleware('api') // Ensure api middleware group is applied
                 ->namespace($this->namespace) // If your controllers are namespaced
                 ->group(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['user-management']; // Example, adjust as necessary
    }

    /**
     * The namespace for the package controllers.
     *
     * @var string|null
     */
    protected $namespace = 'IJIDeals\UserManagement\Http\Controllers';
}
