<?php

namespace IJIDeals\IJIUserSettings\Providers;

use Illuminate\Support\ServiceProvider;
use IJIDeals\IJIUserSettings\Services\UserSettingsService;
use IJIDeals\IJIUserSettings\Services\SettingRegistry;

class IJIUserSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/ijiusersettings.php', 'ijiusersettings'
        );

        $this->app->singleton(SettingRegistry::class, function ($app) {
            // The registry could load declared settings from config files in its constructor or a dedicated method
            return new SettingRegistry(config('ijiusersettings.declared_settings_sources', []));
        });

        $this->app->singleton(UserSettingsService::class, function ($app) {
            return new UserSettingsService(
                $app->make(SettingRegistry::class),
                $app['cache.store'],
                config('ijiusersettings.cache_prefix', 'user_setting.'),
                config('ijiusersettings.cache_duration_user_settings', 60) // Cache user settings for 60 minutes by default
            );
        });
        $this->app->alias(UserSettingsService::class, 'ijiusersettings.user');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/ijiusersettings.php' => config_path('ijiusersettings.php'),
            ], 'ijiusersettings-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'ijiusersettings-migrations');

            // Directory for other packages to publish their setting declarations
            $this->publishes([
                // No file to publish initially, just to make the directory known if packages use it.
                // Or, publish an empty .gitkeep file.
            ], 'ijiusersettings-declarations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Potentially load declared settings from a conventional directory if that approach is chosen
        // $this->app->make(SettingRegistry::class)->loadFromDirectory(config('ijiusersettings.declarations_path'));
    }
}
