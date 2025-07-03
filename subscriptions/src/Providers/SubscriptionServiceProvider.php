<?php

namespace IJIDeals\Subscriptions\Providers;

use IJIDeals\Payments\Events\PaymentSucceeded;
use IJIDeals\Subscriptions\Listeners\HandleSuccessfulPayment;
use Illuminate\Contracts\Events\Dispatcher; // Assuming this event exists
use Illuminate\Support\ServiceProvider; // To be created

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/subscriptions.php', 'subscriptions'
        );

        // Bind any specific services for this package if needed
        // $this->app->bind(MySubscriptionServiceInterface::class, MySubscriptionService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(Dispatcher $events): void
    {
        // Soulbscription's migrations should be published and run from the main app
        // after `composer require lucasdotvin/laravel-soulbscription`.
        // This package might have its own migrations for linking IJIDeals entities to Soulbscription concepts
        // if the direct HasSubscriptions trait is not sufficient. For now, assume no extra migrations.
        // $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/subscriptions.php' => config_path('subscriptions.php'),
            ], 'config');

            // $this->publishes([
            //     __DIR__.'/../Database/migrations/' => database_path('migrations')
            // ], 'migrations');
        }

        $this->registerRoutes();
        $this->registerEventListeners($events);
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (config('subscriptions.api_routes.prefix')) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        }
    }

    /**
     * Register event listeners for the package.
     */
    protected function registerEventListeners(Dispatcher $events): void
    {
        // Example: Listen for successful payments to activate/renew subscriptions
        // This requires `ijideals/payments` to dispatch a `PaymentSucceeded` event
        // that includes information about the payable item (which could be a subscription).
        // $events->listen(
        //     PaymentSucceeded::class,
        //     HandleSuccessfulPayment::class
        // );

        // Listen to Soulbscription events if needed
        // $events->listen(\LucasDotVin\Soulbscription\Events\SubscriptionRenewed::class, ...);
        // $events->listen(\LucasDotVin\Soulbscription\Events\SubscriptionStarted::class, ...);
    }
}
