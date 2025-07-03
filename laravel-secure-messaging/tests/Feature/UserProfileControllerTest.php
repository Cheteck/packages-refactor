<?php

namespace Acme\SecureMessaging\Tests\Feature;

use Acme\SecureMessaging\Tests\TestCase;
use App\Models\User; // Assurez-vous que c'est le bon namespace pour User dans vos tests
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum; // Si vous utilisez Sanctum pour l'authentification

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // S'assurer que la table users est créée via la méthode setupUserTable de TestCase
    }

    public function test_authenticated_user_can_view_their_profile()
    {
        $user = User::factory()->create(['public_key' => 'test_public_key_123']);
        Sanctum::actingAs($user, ['*']); // Authentifier l'utilisateur pour la requête API

        $response = $this->getJson(route('messaging.profile.show'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'public_key']
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'public_key' => 'test_public_key_123',
                ]
            ]);
    }

    public function test_authenticated_user_can_update_their_profile_including_public_key()
    {
        $user = User::factory()->create(['public_key' => 'old_key']);
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'public_key' => 'new_secure_public_key_456',
        ];

        $response = $this->putJson(route('messaging.profile.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully.',
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'public_key' => 'new_secure_public_key_456',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'public_key' => 'new_secure_public_key_456',
        ]);
    }

    public function test_update_profile_validates_email_uniqueness()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        Sanctum::actingAs($user1);

        $response = $this->putJson(route('messaging.profile.update'), [
            'email' => 'user2@example.com', // Try to use user2's email
        ]);

        $response->assertStatus(422) // Unprocessable Entity for validation errors
            ->assertJsonValidationErrors(['email']);
    }


    public function test_authenticated_user_can_get_public_key_of_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create(['public_key' => 'user2_public_key_789']);
        Sanctum::actingAs($user1);

        $response = $this->getJson(route('messaging.users.publicKey', ['userId' => $user2->id]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Public key retrieved successfully.',
                'data' => [
                    'user_id' => $user2->id,
                    'public_key' => 'user2_public_key_789',
                ]
            ]);
    }

    public function test_get_public_key_returns_404_for_unknown_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(route('messaging.users.publicKey', ['userId' => 999])); // Non-existent user ID
        $response->assertStatus(404);
    }

    public function test_get_public_key_returns_404_if_user_has_no_public_key()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create(['public_key' => null]); // User with no public key
        Sanctum::actingAs($user1);

        $response = $this->getJson(route('messaging.users.publicKey', ['userId' => $user2->id]));
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Public key not found for this user or user model does not expose it as "public_key".']);
    }

    public function test_unauthenticated_user_cannot_access_profile_endpoints()
    {
        $this->getJson(route('messaging.profile.show'))->assertStatus(401); // Unauthorized
        $this->putJson(route('messaging.profile.update'), [])->assertStatus(401);

        $user2 = User::factory()->create(['public_key' => 'key']);
        $this->getJson(route('messaging.users.publicKey', ['userId' => $user2->id]))->assertStatus(401);
    }
}
