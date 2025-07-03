<?php

namespace IJIDeals\UserManagement\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use IJIDeals\UserManagement\UserManagementServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            UserManagementServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Optional: run the package's migrations
        // $this->setUpDatabase($app);
    }

    /**
     * Setup the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        // First, create the users table that Laravel's default auth expects
        // This is often needed if your package interacts with auth or User model related features
        // even if your package also provides its own user migration.
        // Adjust this if your User model doesn't extend the default Laravel User.
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Add any other columns that your base User model might expect or that
            // are part of Laravel's default users table if you extend it.
            $table->rememberToken();
            $table->timestamps();
        });


        // Then, run your package's specific migrations
        // Ensure the filename matches your actual migration file
        $migrationFileName = '';
        $migrationFiles = glob(__DIR__.'/../database/migrations/*_create_users_table.php');
        if (!empty($migrationFiles) && file_exists($migrationFiles[0])) {
            $migrationFileName = $migrationFiles[0];
        } else {
            // Fallback or error if not found, though it should exist
            // For this example, let's assume it's the timestamped one if specific name fails
            $migrationFileName = __DIR__.'/../database/migrations/2014_10_12_000000_create_users_table.php';
        }

        if (file_exists($migrationFileName)) {
            $migration = include $migrationFileName;
            $migration->up();
        }
        // If you have more migrations, loop through them or include them specifically.
    }
}
