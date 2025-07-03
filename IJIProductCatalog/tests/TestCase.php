<?php

namespace IJIDeals\IJIProductCatalog\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelPermission\PermissionServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use IJIDeals\IJIProductCatalog\IJIProductCatalogServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'IJIDeals\\IJIProductCatalog\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Create a fake disk for media library tests
        Storage::fake('media');
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            MediaLibraryServiceProvider::class,
            IJIProductCatalogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup Spatie Permissions
        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        // Run the migrations
        include_once __DIR__.'/../database/migrations/2023_02_02_000000_create_brands_table.php';
        (new \CreateBrandsTable)->up();
    }
}
