<?php

namespace IJIDeals\IJICommerce\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\PermissionServiceProvider;
use IJIDeals\IJICommerce\IJICommerceServiceProvider;
use App\Models\User; // Assuming the test User model is in the default App\Models namespace

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

use Spatie\MediaLibrary\MediaLibraryServiceProvider; // Added

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            IJICommerceServiceProvider::class,
            MediaLibraryServiceProvider::class, // Added Spatie MediaLibrary Service Provider
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

        // Configure Spatie Permissions for teams
        $app['config']->set('permission.teams', true);
        $app['config']->set('permission.team_foreign_key', 'shop_id');

        // Configure Spatie MediaLibrary for testing
        // Use a local disk for tests that's cleared afterwards (or use memory if supported and simple)
        $app['config']->set('media-library.disk_name', 'test_disk');
        $app['config']->set('filesystems.disks.test_disk', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/test_disk'),
        ]);
        // Ensure this directory exists and is writable, or clear it in setUp/tearDown

        $this->loadMigrations($app);
        // Policies are registered by IJICommerceServiceProvider

        // After migrations, clean up the test disk for media library
        $this->clearTestMediaDisk();
    }

    /**
     * Load the necessary migrations.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function loadMigrations($app)
    {
        // It's important that Spatie's migrations run BEFORE your 'shops' migration
        // if 'shops' references users or roles in complex ways directly, though typically it shouldn't.
        // The order of providers in getPackageProviders() can also influence migration order.

        // Spatie migrations
        // Orchestra Testbench usually runs migrations from registered service providers.
        // If Spatie's migrations aren't running, ensure its provider is correctly registered first.
        // Explicitly loading them can be a fallback but might indicate deeper setup issues.
        // $this->loadMigrationsFrom(base_path('vendor/spatie/laravel-permission/database/migrations'));

        // Your package's migrations are loaded by IJICommerceServiceProvider.
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Ensure a basic users table exists for authentication and Spatie's user model.
        // This is often needed if the default User model is App\Models\User and no other
        // migrations create it (e.g. in a package-only test environment).
         if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }


    /**
     * Helper function to create a user for tests.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createUser(array $attributes = [])
    {
        User::unguard();
        $user = User::create(array_merge([
            'name' => 'Test User ' . \Illuminate\Support\Str::random(3), // Make name slightly more unique for multiple calls
            'email' => 'test'. \Illuminate\Support\Str::random(5) . rand(100,999) . '@example.com', // Ensure unique email
            'password' => bcrypt('password'),
        ], $attributes));
        User::reguard();

        // Crucially, ensure the User model uses HasRoles for Spatie if not already set up by test environment
        if (!method_exists(User::class, 'assignRole')) {
            // This is a hacky way to add trait in tests if User model is not using it.
            // It's better if User model in tests (App\Models\User) already uses HasRoles.
            // For robust testing, the test User model should be correctly configured.
            // throw new \Exception("User model for testing does not use Spatie\Permission\Traits\HasRoles");
            // For now, we assume the App\Models\User used in tests will have HasRoles.
        }

        return $user;
    }


    /**
     * Helper function to create a shop for tests.
     *
     * @param array $attributes
     * @return \IJIDeals\IJICommerce\Models\Shop
     */
    protected function createShop(array $attributes = [])
    {
        return \IJIDeals\IJICommerce\Models\Shop::create(array_merge([
            'name' => 'Test Shop ' . \Illuminate\Support\Str::random(5),
            'description' => 'A test shop description.',
            'status' => 'active',
        ], $attributes));
    }

    protected function clearTestMediaDisk()
    {
        $diskRoot = storage_path('framework/testing/disks/test_disk');
        if (\Illuminate\Support\Facades\File::isDirectory($diskRoot)) {
            \Illuminate\Support\Facades\File::deleteDirectory($diskRoot);
            \Illuminate\Support\Facades\File::makeDirectory($diskRoot, 0755, true, true); // Recreate for next test
        }
    }

    public function tearDown(): void
    {
        $this->clearTestMediaDisk();
        parent::tearDown();
    }
}
