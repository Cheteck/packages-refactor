<?php

namespace Acme\SecureMessaging;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Broadcast;
use Acme\SecureMessaging\Console\InstallCommand;

class SecureMessagingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/messaging.php', 'messaging'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->configureRateLimiting();
        $this->registerRouteMiddleware($router);
        $this->loadBroadcastChannels();

        if ($this->app->runningInConsole()) {
            $this->publishAssets();
        }
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        foreach (config('messaging.rate_limiting', []) as $name => $limiterConfig) {
            if ($limiterConfig) {
                list($maxAttempts, $decayMinutes) = array_map('trim', explode(',', $limiterConfig));
                $maxAttempts = (int) $maxAttempts;
                $decayMinutes = (int) $decayMinutes;

                RateLimiter::for('messaging.'.$name, function ($request) use ($maxAttempts, $decayMinutes) {
                    return Limit::perMinutes($decayMinutes, $maxAttempts)->by($request->user()?->id ?: $request->ip());
                });
            }
        }
    }

    /**
     * Register the package route middleware.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    protected function registerRouteMiddleware(Router $router)
    {
        foreach (config('messaging.rate_limiting', []) as $name => $config) {
            if ($config) {
                $router->aliasMiddleware('throttle.messaging.'.$name, 'throttle:messaging.'.$name);
            }
        }
    }

    /**
     * Load broadcast channel routes.
     */
    protected function loadBroadcastChannels()
    {
        if (config('broadcasting.default') && file_exists(__DIR__.'/../routes/channels.php')) {
            // This ensures channels are loaded after the application's BroadcastServiceProvider
            // might have called Broadcast::routes().
            $this->app->booted(function () {
                 require __DIR__.'/../routes/channels.php';
            });
        }
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets()
    {
        $this->publishes([
            __DIR__.'/../config/messaging.php' => config_path('messaging.php'),
        ], 'messaging-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'messaging-migrations');

        $this->publishes([
            __DIR__.'/../routes/channels.php' => base_path('routes/channels_secure_messaging.php'),
        ], 'messaging-channels');
    }
}
