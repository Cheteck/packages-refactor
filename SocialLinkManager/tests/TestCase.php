<?php

namespace IJIDeals\SocialLinkManager\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use IJIDeals\SocialLinkManager\SocialLinkManagerServiceProvider;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up any specific factories for this package if not auto-discovered
        // Factory::guessFactoryNamesUsing(
        //     fn (string $modelName) => 'IJIDeals\\SocialLinkManager\\Database\\Factories\\'.class_basename($modelName).'Factory'
        // );
    }

    protected function getPackageProviders($app)
    {
        return [
            SocialLinkManagerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Load the package's migrations
        // The ServiceProvider should handle this, but explicitly loading here can be useful for tests
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Optionally, publish and load the package's config for testing specific values
        // if (file_exists(__DIR__.'/../config/socialinkmanager.php')) {
        //     $app['config']->set('socialinkmanager', require __DIR__.'/../config/socialinkmanager.php');
        // }
    }
}
