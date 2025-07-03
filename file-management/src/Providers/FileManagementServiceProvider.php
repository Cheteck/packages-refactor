<?php

namespace IJIDeals\FileManagement\Providers;

use Illuminate\Support\ServiceProvider;

class FileManagementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge package configuration with the application's configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/file-management.php', 'file-management'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load package routes
        // You might want to separate web and api routes if needed
        // $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        // Load package views
        // This allows you to use views like: view('file-management::your-view')
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'file-management');

        // Publish package configuration
        // This allows users to customize the config by running:
        // php artisan vendor:publish --tag=file-management-config
        $this->publishes([
            __DIR__.'/../../config/file-management.php' => config_path('file-management.php'),
        ], 'file-management-config');

        // Publish package assets (e.g., JS, CSS, images)
        // This allows users to publish assets by running:
        // php artisan vendor:publish --tag=file-management-assets
        // if (file_exists(__DIR__.'/../../public')) {
        //     $this->publishes([
        //         __DIR__.'/../../public' => public_path('vendor/file-management'),
        //     ], 'file-management-assets');
        // }
    }
}
