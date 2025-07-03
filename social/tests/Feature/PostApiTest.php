<?php

namespace IJIDeals\Social\Tests\Feature;

use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase; // Root TestCase

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure service providers for packages are loaded.
        // They should be auto-discovered if correctly registered in config/app.php's 'providers' array.
        // $this->app->register(\IJIDeals\Social\Providers\SocialServiceProvider::class);

        $this->user = User::factory()->create();
    }

    public function test_can_create_post()
    {
        $this->actingAs($this->user, 'sanctum');
        $postData = ['contenu' => 'This is a test post.', 'type' => 'texte'];
        $response = $this->postJson('/api/v1/social/posts', $postData);
        $response->assertStatus(201)
            ->assertJsonFragment(['contenu' => 'This is a test post.']);

        $this->assertDatabaseHas('posts', [
            'contenu' => 'This is a test post.',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_get_posts()
    {
        $this->actingAs($this->user, 'sanctum');
        Post::factory()->for($this->user)->count(3)->create();

        $response = $this->getJson('/api/v1/social/posts');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
