<?php

namespace IJIDeals\Pricing\Providers;

use Illuminate\Support\ServiceProvider;

class PricingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../Config/pricing.php', 'pricing' // Corrected path if config is at root/Config
        );

        $this->app->singleton(\IJIDeals\Pricing\Services\PricingService::class, function ($app) {
            return new \IJIDeals\Pricing\Services\PricingService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations'); // Corrected path if migrations are in src/Database

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../Config/pricing.php' => config_path('pricing.php'),
            ], 'config'); // Standard tag 'config'

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations'); // Standard tag 'migrations'
        }

        // If the package had views, translations, or policies, they would be registered/published here.
        // Example for translations:
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pricing');
        // $this->publishes([
        //     __DIR__.'/../resources/lang' => lang_path('vendor/pricing'),
        // ], 'pricing-translations');

        // Example for views:
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'pricing');
        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/pricing'),
        // ], 'pricing-views');

        // Example for policies (if models exist and require authorization):
        // $this->registerPolicies();
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    // public function registerPolicies(): void
    // {
    //     // Gate::policy(YourModel::class, YourModelPolicy::class);
    // }
}
