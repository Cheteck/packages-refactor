<?php

namespace IJIDeals\NotificationsManager\Providers;

use IJIDeals\NotificationsManager\Models\UserNotificationPreference;
use IJIDeals\NotificationsManager\Policies\UserNotificationPreferencePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider; // Will be created

class NotificationsManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/notifications-manager.php', 'notifications-manager'
        );

        // Bind the service interface to its implementation
        $this->app->bind(
            \IJIDeals\NotificationsManager\Services\UserNotificationPreferenceServiceInterface::class,
            \IJIDeals\NotificationsManager\Services\UserNotificationPreferenceService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/notifications-manager.php' => config_path('notifications-manager.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }

        $this->registerRoutes();
        $this->registerPolicies();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (config('notifications-manager.api_routes.prefix')) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        }
    }

    /**
     * Register the application's policies.
     */
    public function registerPolicies(): void
    {
        Gate::policy(UserNotificationPreference::class, UserNotificationPreferencePolicy::class);
    }
}
