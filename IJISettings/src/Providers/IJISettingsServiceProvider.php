<?php

namespace IJIDeals\IJISettings\Providers;

use Illuminate\Support\ServiceProvider;
use IJIDeals\IJISettings\Services\PlatformSettingService;

class IJISettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/ijisettings.php', 'ijisettings'
        );

        $this->app->singleton(PlatformSettingService::class, function ($app) {
            return new PlatformSettingService(
                $app['cache.store'],
                config('ijisettings.cache_prefix', 'platform_setting.'),
                config('ijisettings.cache_duration', null) // null for rememberForever by default
            );
        });
        $this->app->alias(PlatformSettingService::class, 'ijisettings.platform');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/ijisettings.php' => config_path('ijisettings.php'),
            ], 'ijisettings-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'ijisettings-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
