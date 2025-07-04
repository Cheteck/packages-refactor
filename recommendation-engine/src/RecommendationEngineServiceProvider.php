<?php

namespace IJIDeals\RecommendationEngine;

use Illuminate\Support\ServiceProvider;

class RecommendationEngineServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\IJIDeals\RecommendationEngine\Services\RecommendationService::class, function ($app) {
            return new \IJIDeals\RecommendationEngine\Services\RecommendationService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
