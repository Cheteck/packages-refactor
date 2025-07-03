<?php

namespace Acme\SecureMessaging\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Acme\SecureMessaging\SecureMessagingServiceProvider;
use App\Models\User; // L'application hôte devra avoir un modèle User
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // S'assurer que la facade Auth fonctionne et qu'un utilisateur peut être authentifié
        $this->setupUserTable();

        // Exécuter les migrations du package
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        // Charger les factories du package
        $this->loadEloquentFactoriesFrom(__DIR__.'/../database/factories');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            SecureMessagingServiceProvider::class,
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
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Configurer le modèle User pour le package
        $app['config']->set('messaging.user_model', User::class);
        // Configurer d'autres options du package si nécessaire pour les tests
        $app['config']->set('messaging.features.ephemeral_messages.enabled', true);
        $app['config']->set('messaging.features.attachments.enabled', true);
        $app['config']->set('auth.providers.users.model', User::class); // Pour l'authentification Sanctum

        // Configurer Sanctum pour les tests
        $app['config']->set('sanctum.guard', 'sanctum'); // ou null pour utiliser le guard par défaut
        $app['config']->set('auth.guards.sanctum', ['driver' => 'sanctum', 'provider' => 'users']);


        // S'assurer que les routes de diffusion sont chargées si on les teste
        // $app['config']->set('broadcasting.default', 'pusher'); // ou un autre driver de test
        // $app['config']->set('broadcasting.connections.pusher.key', 'test_key');
        // $app['config']->set('broadcasting.connections.pusher.secret', 'test_secret');
        // $app['config']->set('broadcasting.connections.pusher.app_id', 'test_app_id');
    }

    /**
     * Helper pour créer la table users si elle n'existe pas déjà pour les tests.
     */
    protected function setupUserTable()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->text('public_key')->nullable(); // Important pour les tests de profil
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }


    /**
     * Helper pour créer un utilisateur pour les tests.
     * @param array $attributes
     * @return User
     */
    protected function createUser(array $attributes = []): User
    {
        UserFactory::new()->create($attributes); // Cela suppose que vous avez une UserFactory
                                                // Si non, créez-en une ou utilisez User::create
        // Fallback si UserFactory n'est pas disponible dans ce contexte de package simple
        if (!class_exists(\Database\Factories\UserFactory::class) && !class_exists(\Acme\SecureMessaging\Tests\Factories\UserFactory::class) ) {
             return User::create(array_merge([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'public_key' => null, // Peut être défini dans les attributs
            ], $attributes));
        }
        // Si une factory est définie (par ex. dans l'app hôte ou le package)
        return User::factory()->create($attributes);
    }
}

// Définir une UserFactory basique si elle n'existe pas dans l'environnement de test.
// Idéalement, l'application hôte fournit cela, ou le package a ses propres factories de test.
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User; // Ajuster si le modèle User est dans un autre namespace pour les tests
use Illuminate\Support\Str;

// La UserFactory est maintenant attendue de l'application hôte ou doit être
// explicitement définie dans les factories du package si le package fournit sa propre User pour les tests.
// Pour les tests de ce package, on utilise App\Models\User qui devrait avoir sa propre factory.
// Si ce n'est pas le cas, le User::factory() dans les tests échouera à moins que Testbench n'en fournisse une par défaut.
// Le code `createUser` a un fallback simple User::create().
// Il est préférable de s'assurer que l'environnement de test fournit UserFactory.
// La définition en ligne ici est supprimée car elle est maintenant dans TestCase.
// (Correction: la définition de UserFactory en ligne était déjà dans TestCase, pas MessageControllerTest)
// Le code original dans TestCase pour UserFactory reste valide comme fallback.
