<?php

namespace IJIDeals\IJICommerce\Tests\Feature;

use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Models\ProductProposal;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User; // Test User model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ProductProposalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $shopOwner;
    protected User $anotherUser;
    protected Shop $testShop;
    protected Role $ownerRole; // For shop ownership

    public function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        // Create a role that can manage proposals, could be 'Owner' or a specific 'Product Manager'
        // For simplicity, let's assume 'Owner' can manage proposals for their shop.

        // Create users
        $this->shopOwner = $this->createUser(['email' => 'owner.proposals@example.com']);
        $this->anotherUser = $this->createUser(['email' => 'other.proposals@example.com']);

        // Create a shop and assign owner
        $this->testShop = $this->createShop(['name' => 'Proposal Test Shop']);
        $this->shopOwner->assignRole($this->ownerRole->name, $this->testShop);

        // Ensure the User model has HasRoles trait (critical for Spatie)
        if (!method_exists(User::class, 'assignRole')) {
            throw new \Exception("The test User model (App\\Models\\User) does not use Spatie\\Permission\\Traits\\HasRoles. Please add it.");
        }
    }

    /** @test */
    public function shop_owner_can_submit_a_product_proposal_for_their_shop()
    {
        $this->actingAs($this->shopOwner, 'sanctum');

        $proposalData = [
            'shop_id' => $this->testShop->id,
            'name' => 'New Amazing Gadget',
            'description' => 'This gadget will change the world.',
            'proposed_brand_name' => 'GadgetCorp',
            'proposed_category_name' => 'Electronics',
            'proposed_specifications' => ['color' => 'blue', 'weight' => '200g'],
            'proposed_images_payload' => ['/uploads/temp/gadget.jpg'],
        ];

        $response = $this->postJson(route('ijicommerce.api.product-proposals.store'), $proposalData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Amazing Gadget', 'status' => 'pending']);

        $this->assertDatabaseHas('product_proposals', [
            'shop_id' => $this->testShop->id,
            'name' => 'New Amazing Gadget',
        ]);
    }

    /** @test */
    public function user_cannot_submit_proposal_for_a_shop_they_dont_manage()
    {
        $this->actingAs($this->anotherUser, 'sanctum'); // This user is not owner of $this->testShop

        $proposalData = [
            'shop_id' => $this->testShop->id, // Attempting to submit for $this->testShop
            'name' => 'Unauthorized Gadget',
        ];

        $response = $this->postJson(route('ijicommerce.api.product-proposals.store'), $proposalData);
        $response->assertStatus(403);
    }

    /** @test */
    public function shop_owner_can_list_their_own_product_proposals()
    {
        $this->actingAs($this->shopOwner, 'sanctum');

        ProductProposal::create([
            'shop_id' => $this->testShop->id, 'name' => 'Proposal 1', 'status' => 'pending'
        ]);
        ProductProposal::create([
            'shop_id' => $this->testShop->id, 'name' => 'Proposal 2', 'status' => 'approved'
        ]);

        // A proposal for another shop by another user (should not appear)
        $otherShop = $this->createShop(['name' => 'Other Shop']);
        $this->anotherUser->assignRole($this->ownerRole->name, $otherShop);
        ProductProposal::create([
            'shop_id' => $otherShop->id, 'name' => 'Proposal 3 by other', 'status' => 'pending'
        ]);


        $response = $this->getJson(route('ijicommerce.api.product-proposals.index'));
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Proposal 1'])
            ->assertJsonFragment(['name' => 'Proposal 2'])
            ->assertJsonMissing(['name' => 'Proposal 3 by other'])
            ->assertJsonCount(2, 'data'); // Assuming pagination and 'data' wrapper
    }

    /** @test */
    public function shop_owner_can_view_their_specific_product_proposal()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        $proposal = ProductProposal::create([
            'shop_id' => $this->testShop->id, 'name' => 'Viewable Proposal', 'status' => 'pending'
        ]);

        $response = $this->getJson(route('ijicommerce.api.product-proposals.show', $proposal->id));
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Viewable Proposal']);
    }

    /** @test */
    public function user_cannot_view_proposal_from_a_shop_they_dont_manage()
    {
        // $this->shopOwner creates a proposal
        $proposal = ProductProposal::create([
            'shop_id' => $this->testShop->id, 'name' => 'Private Proposal', 'status' => 'pending'
        ]);

        $this->actingAs($this->anotherUser, 'sanctum'); // anotherUser tries to view it
        $response = $this->getJson(route('ijicommerce.api.product-proposals.show', $proposal->id));
        $response->assertStatus(403);
    }
}
