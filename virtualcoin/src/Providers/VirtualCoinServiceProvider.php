<?php

namespace IJIDeals\VirtualCoin\Providers; // Corrected namespace

use Illuminate\Support\ServiceProvider;

// Assuming a service might be created later, or specific bindings are needed.
// use IJIDeals\VirtualCoin\Services\VirtualCoinService;

class VirtualCoinServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/virtualcoin.php', 'virtualcoin'
        );

        $this->app->singleton(\IJIDeals\VirtualCoin\Services\WalletService::class, function ($app) {
            return new \IJIDeals\VirtualCoin\Services\WalletService();
        });
        $this->app->alias(\IJIDeals\VirtualCoin\Services\WalletService::class, 'virtualcoin.wallet');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations'); // Corrected path

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/virtualcoin.php' => config_path('virtualcoin.php'),
            ], 'virtualcoin-config'); // More specific tag

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'virtualcoin-migrations'); // More specific tag
        }

        // Define a Gate for balance adjustments if it doesn't exist
        // This is a basic example; your application might have a more sophisticated permission system.
        // Ensure Gate facade is imported: use Illuminate\Support\Facades\Gate;
        if (!\Illuminate\Support\Facades\Gate::has('adjust-virtual-coin-balance')) {
            \Illuminate\Support\Facades\Gate::define('adjust-virtual-coin-balance', function ($user, $targetWallet = null) {
                // Example: Only users with a specific role (e.g., 'super-admin') can adjust any balance.
                // Or, a user might be able to adjust specific wallets they manage (not typical for virtual coins).
                // Replace with your actual permission logic.
                // Ensure $user is an instance of your User model.
                if (!$user || !method_exists($user, 'hasRole')) {
                    return false;
                }
                return $user->hasRole('super-admin'); // Ensure your User model has a hasRole method
            });
        }
    }
}
