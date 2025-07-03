<?php

namespace IJIDeals\Social\Providers;

use IJIDeals\Social\Models\Comment;
use IJIDeals\Social\Models\Follow;
use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Reaction;
use IJIDeals\Social\Policies\CommentPolicy;
use IJIDeals\Social\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

// Add Like and Follow policies if they are created later and need explicit registration.
// For now, LikeController and FollowController handle authorization directly or via simple checks.

class SocialServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge package configuration (if any)
        // $this->mergeConfigFrom(
        //     __DIR__.'/../../config/social.php', 'social'
        // );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load API routes
        // The mapApiRoutes method already loads the routes from api.php within its group.
        // No need for a separate loadRoutesFrom call for the same file.
        $this->mapApiRoutes();

        // Load Factories
        // Laravel automatically discovers factories in conventional paths (like database/factories)
        // within the registered namespace ('IJIDeals\Social\Database\Factories' should work if PSR-4 is set up).
        // If factories were in a non-standard location or for explicit loading:
        // $this->app->make(\Illuminate\Database\Eloquent\Factory::class)->load(__DIR__.'/../../database/factories');
        // However, this is often not needed if composer.json's autoload includes the factories' namespace.

        // Register Policies
        $this->registerPolicies();

        // Make package config files publishable (optional example)
        // $this->publishes([
        //     __DIR__.'/../../config/social.php' => config_path('social.php'),
        // ], 'social-config'); // Tagging for specific publishing

        // Make package views publishable (optional example)
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'social');
        // $this->publishes([
        //     __DIR__.'/../../resources/views' => resource_path('views/vendor/social'),
        // ], 'social-views'); // Tagging for specific publishing
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        // Register other policies if they exist (e.g., LikePolicy, FollowPolicy)
        // Gate::policy(Reaction::class, LikePolicy::class); // Assuming LikePolicy would handle Reaction
        // Gate::policy(Follow::class, FollowPolicy::class);
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace('IJIDeals\\Social\\Http\\Controllers') // Corrected namespace casing for consistency
            ->group(__DIR__.'/../../routes/api.php');
    }
}
