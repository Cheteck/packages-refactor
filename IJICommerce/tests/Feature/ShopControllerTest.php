<?php

namespace IJIDeals\IJICommerce\Tests\Feature;

use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Tests\TestCase; // Your package's base TestCase
use App\Models\User; // Assuming User model for tests
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $memberUser;
    protected User $otherUser;
    protected Shop $testShop;
    protected Role $ownerRole;
    protected Role $editorRole;

    public function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->ownerUser = $this->createUser(['email' => 'owner@example.com']);
        $this->memberUser = $this->createUser(['email' => 'member@example.com']);
        $this->otherUser = $this->createUser(['email' => 'other@example.com']);

        // Create roles (Spatie will use the default guard from auth.php)
        $this->ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $this->editorRole = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
        // Ensure Spatie config is set for teams for these roles to be assignable to a team
        // This is handled in TestCase::getEnvironmentSetUp

        // Create a shop
        $this->testShop = $this->createShop(['name' => 'Main Test Shop']);

        // Assign owner role to ownerUser for the testShop
        $this->ownerUser->assignRole($this->ownerRole->name, $this->testShop);
        $this->memberUser->assignRole($this->editorRole->name, $this->testShop);

        // Sanity check roles are assigned
        // $this->assertTrue($this->ownerUser->hasRole('Owner', $this->testShop));
        // $this->assertTrue($this->memberUser->hasRole('Editor', $this->testShop));
    }

    /** @test */
    public function authenticated_user_can_create_a_shop_and_becomes_owner()
    {
        $this->actingAs($this->otherUser, 'sanctum'); // Use sanctum for API tests

        $shopData = [
            'name' => 'New Awesome Shop',
            'description' => 'A brand new shop for testing.',
            'contact_email' => 'newshop@example.com',
            'status' => 'active',
        ];

        $response = $this->postJson(route('ijicommerce.api.shops.store'), $shopData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Awesome Shop']);

        $createdShop = Shop::where('name', 'New Awesome Shop')->first();
        $this->assertNotNull($createdShop);

        // Verify the creator is now an 'Owner' of this new shop
        $this->assertTrue($this->otherUser->hasRole('Owner', $createdShop));
    }

    /** @test */
    public function shop_owner_can_view_their_shop()
    {
        $this->actingAs($this->ownerUser, 'sanctum');
        $response = $this->getJson(route('ijicommerce.api.shops.show', $this->testShop->id));
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $this->testShop->id, 'name' => $this->testShop->name]);
    }

    /** @test */
    public function non_team_member_cannot_view_a_shop_if_view_policy_is_strict()
    {
        // Assuming ShopPolicy@view requires some role membership for the shop
        // If ShopPolicy@view is very permissive (e.g. any authenticated user), this test needs adjustment
        $this->actingAs($this->otherUser, 'sanctum');
        $response = $this->getJson(route('ijicommerce.api.shops.show', $this->testShop->id));
        $response->assertStatus(403); // Or based on your policy's view logic
    }


    /** @test */
    public function shop_owner_can_update_their_shop()
    {
        $this->actingAs($this->ownerUser, 'sanctum');
        $updateData = ['name' => 'Updated Shop Name', 'description' => 'Updated description.'];

        $response = $this->putJson(route('ijicommerce.api.shops.update', $this->testShop->id), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Shop Name']);
        $this->assertDatabaseHas('shops', ['id' => $this->testShop->id, 'name' => 'Updated Shop Name']);
    }

    /** @test */
    public function non_owner_or_admin_cannot_update_shop()
    {
        $this->actingAs($this->memberUser, 'sanctum'); // memberUser is an Editor, not Owner/Admin
        $updateData = ['name' => 'Attempted Update Shop Name'];

        $response = $this->putJson(route('ijicommerce.api.shops.update', $this->testShop->id), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function shop_owner_can_delete_their_shop()
    {
        $this->actingAs($this->ownerUser, 'sanctum');
        $response = $this->deleteJson(route('ijicommerce.api.shops.destroy', $this->testShop->id));

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Shop deleted successfully.']);
        $this->assertDatabaseMissing('shops', ['id' => $this->testShop->id]); // Assuming hard delete for test
    }

    /** @test */
    public function non_owner_cannot_delete_shop()
    {
        $this->actingAs($this->memberUser, 'sanctum');
        $response = $this->deleteJson(route('ijicommerce.api.shops.destroy', $this->testShop->id));
        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_list_shops_they_are_a_member_of()
    {
        // ownerUser is owner of $this->testShop
        // memberUser is editor of $this->testShop
        // otherUser is member of no shops initially

        // Acting as ownerUser
        $this->actingAs($this->ownerUser, 'sanctum');
        $responseOwner = $this->getJson(route('ijicommerce.api.shops.index'));
        $responseOwner->assertStatus(200)
            ->assertJsonFragment(['id' => $this->testShop->id]);

        // Check if the response contains only one shop for ownerUser (if they only own one)
        // $responseOwner->assertJsonCount(1, 'data'); // If pagination is used, 'data' is the key

        // Acting as memberUser
        $this->actingAs($this->memberUser, 'sanctum');
        $responseMember = $this->getJson(route('ijicommerce.api.shops.index'));
        $responseMember->assertStatus(200)
            ->assertJsonFragment(['id' => $this->testShop->id]);

        // Acting as otherUser (should not see $this->testShop)
        $this->actingAs($this->otherUser, 'sanctum');
        $responseOther = $this->getJson(route('ijicommerce.api.shops.index'));
        $responseOther->assertStatus(200)
            ->assertJsonMissing(['id' => $this->testShop->id]);
    }
}
