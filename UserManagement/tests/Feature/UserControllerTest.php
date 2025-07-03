<?php

namespace IJIDeals\UserManagement\Tests\Feature;

use IJIDeals\UserManagement\Models\User;
use IJIDeals\UserManagement\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase; // This trait will run migrations for you if configured correctly in TestCase

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Also run the package's migrations for this test class
        $this->setUpDatabase($app);
    }

    /** @test */
    public function it_can_show_a_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'username' => 'testuser',
        ]);

        $response = $this->get(route('user-management.users.show', $user->id));

        $response->assertStatus(200);
        $response->assertViewIs('user-management::users.show');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee($user->username);
        // Add checks for new fields that would be visible on the show page
        $response->assertSee($user->bio); // Assuming bio is shown
    }

    /** @test */
    public function it_can_show_the_edit_form_for_a_user()
    {
        $user = User::create([
            'name' => 'Test User Edit',
            'email' => 'testedit@example.com',
            'password' => bcrypt('password'),
            'username' => 'testedituser',
            'bio' => 'Some bio',
            'birthdate' => '1990-01-01',
            'location' => 'Testville',
        ]);

        $response = $this->get(route('user-management.users.edit', $user->id));

        $response->assertStatus(200);
        $response->assertViewIs('user-management::users.edit');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee($user->username);
        $response->assertSee($user->bio);
        $response->assertSee('1990-01-01'); // Check for birthdate in form value
        $response->assertSee($user->location);
    }

    /** @test */
    public function it_can_update_a_user_via_web_request()
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => bcrypt('password'),
            'username' => 'originaluser',
            'bio' => 'Original bio',
            'birthdate' => '1980-05-15',
            'gender' => 'Male',
            'phone' => '1234567890',
            'preferred_language' => 'en',
            'location' => 'Old City',
            'website' => 'http://original.com',
            'profile_photo_path' => '/path/to/old_profile.jpg',
            'cover_photo_path' => '/path/to/old_cover.jpg',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com', // Keep email unique for tests if needed
            'username' => 'updateduser',
            'bio' => 'Updated bio',
            'birthdate' => '1995-02-20',
            'gender' => 'Female',
            'phone' => '0987654321',
            'preferred_language' => 'fr',
            'location' => 'New City',
            'website' => 'https://updated.com',
            'profile_photo_path' => '/path/to/new_profile.jpg',
            'cover_photo_path' => '/path/to/new_cover.jpg',
        ];

        $response = $this->put(route('user-management.users.update', $user->id), $updateData);

        // Web request should redirect to show page on success by default
        $response->assertRedirect(route('user-management.users.show', $user->id));
        $response->assertSessionHas('success', 'User updated successfully.');


        $this->assertDatabaseHas('users', array_merge(['id' => $user->id], $updateData));
    }

    /** @test */
    public function it_can_update_a_user_via_api_request()
    {
        $user = User::create([
            'name' => 'API Original Name',
            'email' => 'apioriginal@example.com',
            'password' => bcrypt('password'),
        ]);

        $updateData = [
            'name' => 'API Updated Name',
            'bio' => 'API User Bio',
            'location' => 'API Location',
        ];

        $response = $this->putJson(route('api.user-management.users.update', $user->id), $updateData);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'User updated successfully']);
        $response->assertJsonFragment(['name' => 'API Updated Name']); // Check if user data is in response

        $this->assertDatabaseHas('users', array_merge(['id' => $user->id], $updateData));
    }
}
