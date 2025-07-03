<?php

namespace IJIDeals\Sponsorship\Providers;

use IJIDeals\Sponsorship\Models\SponsoredPost;
use IJIDeals\Sponsorship\Policies\SponsoredPostPolicy;
use IJIDeals\Sponsorship\Services\SponsorshipService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SponsorshipServiceProvider extends ServiceProvider
{
    protected $policies = [
        SponsoredPost::class => SponsoredPostPolicy::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/sponsorship.php', 'sponsorship'
        );

        $this->app->singleton(SponsorshipService::class, function ($app) {
            return new SponsorshipService;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/sponsorship.php' => config_path('sponsorship.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../Database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }

        $this->registerPolicies();
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
        }
    }
}
