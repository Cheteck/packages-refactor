<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Tests\TestCase; // Your package's base TestCase
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductProposalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $shop = $this->createShop(); // Helper from base TestCase or create here
        $data = [
            'shop_id' => $shop->id,
            'name' => 'Test Proposal',
            'description' => 'This is a test description.',
            'proposed_brand_name' => 'Test Brand',
            'proposed_category_name' => 'Test Category',
            'proposed_specifications' => ['color' => 'red', 'size' => 'L'],
            'proposed_images_payload' => ['path/to/image1.jpg'],
            'status' => 'pending',
            'admin_notes' => null,
        ];
        $proposal = ProductProposal::create($data);

        $this->assertInstanceOf(ProductProposal::class, $proposal);
        $this->assertEquals('Test Proposal', $proposal->name);
        $this->assertEquals(['color' => 'red', 'size' => 'L'], $proposal->proposed_specifications);
        $this->assertEquals($shop->id, $proposal->shop_id);
    }

    /** @test */
    public function it_belongs_to_a_shop()
    {
        $shop = $this->createShop();
        $proposal = ProductProposal::factory()->create(['shop_id' => $shop->id]); // Assuming you'll create factories

        $this->assertInstanceOf(Shop::class, $proposal->shop);
        $this->assertEquals($shop->id, $proposal->shop->id);
    }

    /** @test */
    public function status_defaults_to_pending_if_not_provided_in_migration()
    {
        // Note: The model doesn't set default status, migration does.
        // This test would be more about checking DB default if model doesn't override.
        $shop = $this->createShop();
         $proposal = ProductProposal::create([
            'shop_id' => $shop->id,
            'name' => 'Proposal with default status',
        ]);
        $this->assertEquals('pending', $proposal->fresh()->status); // Check migration default
    }
}

// To use factories, you would need to define them:
// namespace Database\Factories;
// use Illuminate\Database\Eloquent\Factories\Factory;
// use IJIDeals\IJICommerce\Models\ProductProposal;
// class ProductProposalFactory extends Factory {
//     protected $model = ProductProposal::class;
//     public function definition() { /* ... */ }
// }
