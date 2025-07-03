<?php

namespace IJIDeals\IJICommerce\Tests\Feature\Admin;

use IJIDeals\IJICommerce\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminProductProposalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdminUser;
    protected User $shopOwnerUser;
    protected Shop $testShop;
    protected Role $platformAdminRole;
    protected Role $shopOwnerRole; // For the shop owner

    public function setUp(): void
    {
        parent::setUp();
        $this->platformAdminRole = Role::firstOrCreate(['name' => 'Platform Admin', 'guard_name' => 'web']);
        $this->shopOwnerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);

        $this->platformAdminUser = $this->createUser(['email' => 'admin.proposals@example.com']);
        $this->platformAdminUser->assignRole($this->platformAdminRole->name);

        $this->shopOwnerUser = $this->createUser(['email' => 'shopowner.proposals@example.com']);
        $this->testShop = $this->createShop(['name' => 'Shop For Proposals']);
        $this->shopOwnerUser->assignRole($this->shopOwnerRole->name, $this->testShop);
    }

    private function createPendingProposal(): ProductProposal
    {
        return ProductProposal::create([
            'shop_id' => $this->testShop->id,
            'name' => 'Pending Gadget Proposal',
            'description' => 'A cool gadget awaiting approval.',
            'proposed_brand_name' => 'GadgetCo',
            'proposed_category_name' => 'Tech',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function platform_admin_can_list_pending_product_proposals()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $this->createPendingProposal();
        ProductProposal::create([ // Approved one, should not show by default
            'shop_id' => $this->testShop->id, 'name' => 'Approved Proposal', 'status' => 'approved'
        ]);


        $response = $this->getJson(route('ijicommerce.api.admin.product-proposals.index.admin', ['status' => 'pending']));
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Pending Gadget Proposal'])
            ->assertJsonMissing(['name' => 'Approved Proposal']);
            // ->assertJsonCount(1, 'data'); // If pagination is active
    }

    /** @test */
    public function platform_admin_can_approve_a_product_proposal()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $proposal = $this->createPendingProposal();

        $approvalData = [
            'name' => $proposal->name, // Admin can override these
            'description' => $proposal->description,
            'brand_name' => $proposal->proposed_brand_name,
            'category_name' => $proposal->proposed_category_name,
            'status' => 'active', // Status for the new MasterProduct
            'admin_notes_for_proposal' => 'Looks good!',
        ];

        $response = $this->postJson(route('ijicommerce.api.admin.product-proposals.approve', $proposal->id), $approvalData);

        $response->assertStatus(201) // MasterProduct created
            ->assertJsonPath('proposal.status', 'approved')
            ->assertJsonPath('proposal.admin_notes', 'Looks good!')
            ->assertJsonPath('master_product.name', $proposal->name);

        $this->assertDatabaseHas('master_products', ['name' => $proposal->name, 'created_by_proposal_id' => $proposal->id]);
        $this->assertDatabaseHas('product_proposals', ['id' => $proposal->id, 'status' => 'approved']);

        $masterProduct = MasterProduct::where('created_by_proposal_id', $proposal->id)->first();
        $this->assertNotNull($masterProduct);
        $this->assertEquals($masterProduct->id, $proposal->fresh()->approved_master_product_id);
    }

    /** @test */
    public function platform_admin_can_reject_a_product_proposal()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $proposal = $this->createPendingProposal();

        $rejectionData = ['admin_notes' => 'Not enough detail.'];

        $response = $this->postJson(route('ijicommerce.api.admin.product-proposals.reject', $proposal->id), $rejectionData);
        $response->assertStatus(200)
            ->assertJsonPath('proposal.status', 'rejected')
            ->assertJsonPath('proposal.admin_notes', 'Not enough detail.');

        $this->assertDatabaseHas('product_proposals', ['id' => $proposal->id, 'status' => 'rejected']);
    }

    /** @test */
    public function shop_owner_cannot_approve_proposals()
    {
        $this->actingAs($this->shopOwnerUser, 'sanctum');
        $proposal = $this->createPendingProposal();
        $response = $this->postJson(route('ijicommerce.api.admin.product-proposals.approve', $proposal->id), ['name' => 'test', 'status' => 'active']);
        $response->assertStatus(403);
    }
}
