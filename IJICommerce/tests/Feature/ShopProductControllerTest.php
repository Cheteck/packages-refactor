<?php

namespace IJIDeals\IJICommerce\Tests\Feature;

use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ShopProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $shopOwner;
    protected User $anotherShopOwner;
    protected Shop $testShop;
    protected Shop $otherShop;
    protected MasterProduct $masterProduct1;
    protected MasterProduct $masterProduct2;
    protected Role $ownerRole;
    protected Role $editorRole; // Role that can manage shop products

    public function setUp(): void
    {
        parent::setUp();
        $this->ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $this->editorRole = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']); // Assumed can manage products

        $this->shopOwner = $this->createUser(['email' => 'owner.shopprods@example.com']);
        $this->anotherShopOwner = $this->createUser(['email' => 'otherowner.shopprods@example.com']);

        $this->testShop = $this->createShop(['name' => 'My Productive Shop']);
        $this->otherShop = $this->createShop(['name' => 'Their Productive Shop']);

        $this->shopOwner->assignRole($this->ownerRole->name, $this->testShop);
        $this->shopOwner->assignRole($this->editorRole->name, $this->testShop); // Give editor role too for manageShopProducts policy
        $this->anotherShopOwner->assignRole($this->ownerRole->name, $this->otherShop);

        $this->masterProduct1 = MasterProduct::create(['name' => 'Global Gadget Alpha', 'status' => 'active']);
        $this->masterProduct2 = MasterProduct::create(['name' => 'Global Gizmo Beta', 'status' => 'active']);
        MasterProduct::create(['name' => 'Inactive Global Product', 'status' => 'archived']);
    }

    /** @test */
    public function shop_owner_can_list_available_master_products_for_their_shop()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        // Shop already lists masterProduct1
        $this->testShop->shopProducts()->create([
            'master_product_id' => $this->masterProduct1->id, 'price' => 10, 'stock_quantity' => 5
        ]);

        $response = $this->getJson(route('ijicommerce.api.shops.shop-products.available-master.index', $this->testShop));

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $this->masterProduct2->name]) // Gizmo Beta should be available
            ->assertJsonMissing(['name' => $this->masterProduct1->name]) // Alpha already listed
            ->assertJsonMissing(['name' => 'Inactive Global Product']); // Inactive should not show
    }

    /** @test */
    public function shop_owner_can_add_a_master_product_to_their_shop_listings()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        $listingData = [
            'master_product_id' => $this->masterProduct1->id,
            'price' => 120.50,
            'stock_quantity' => 30,
            'is_visible_in_shop' => true,
        ];

        $response = $this->postJson(route('ijicommerce.api.shops.shop-products.store', $this->testShop), $listingData);

        $response->assertStatus(201)
            ->assertJsonFragment(['price' => "120.50"]); // Price cast to string in JSON

        $this->assertDatabaseHas('shop_products', [
            'shop_id' => $this->testShop->id,
            'master_product_id' => $this->masterProduct1->id,
            'price' => 120.50,
            'stock_quantity' => 30,
        ]);
    }

    /** @test */
    public function shop_owner_cannot_add_same_master_product_twice()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        $this->testShop->shopProducts()->create([
            'master_product_id' => $this->masterProduct1->id, 'price' => 10, 'stock_quantity' => 5
        ]);

        $listingData = [
            'master_product_id' => $this->masterProduct1->id, // Attempting to add again
            'price' => 150.00,
            'stock_quantity' => 10,
        ];
        $response = $this->postJson(route('ijicommerce.api.shops.shop-products.store', $this->testShop), $listingData);
        $response->assertStatus(422) // Validation error due to unique rule
            ->assertJsonPath('errors.master_product_id.0', 'This product is already listed in your shop.');
    }


    /** @test */
    public function shop_owner_can_update_their_shop_product_listing()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        $shopProduct = $this->testShop->shopProducts()->create([
            'master_product_id' => $this->masterProduct1->id, 'price' => 10, 'stock_quantity' => 5
        ]);

        $updateData = ['price' => 12.99, 'stock_quantity' => 3];
        $response = $this->putJson(route('ijicommerce.api.shops.shop-products.update', [$this->testShop, $shopProduct]), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['price' => "12.99"]);
        $this->assertDatabaseHas('shop_products', ['id' => $shopProduct->id, 'price' => 12.99, 'stock_quantity' => 3]);
    }

    /** @test */
    public function shop_owner_can_acknowledge_master_product_update()
    {
        $this->actingAs($this->shopOwner, 'sanctum');
        $shopProduct = $this->testShop->shopProducts()->create([
            'master_product_id' => $this->masterProduct1->id,
            'price' => 10,
            'stock_quantity' => 5,
            'needs_review_by_shop' => true, // Mark as needing review
            'is_visible_in_shop' => false,
            'master_version_hash' => 'old_hash'
        ]);

        $response = $this->postJson(route('ijicommerce.api.shops.shop-products.acknowledge-update', [$this->testShop, $shopProduct]));
        $response->assertStatus(200)
            ->assertJsonPath('shop_product.needs_review_by_shop', false)
            ->assertJsonPath('shop_product.is_visible_in_shop', true);

        $freshShopProduct = $shopProduct->fresh();
        $expectedNewHash = md5(serialize($freshShopProduct->masterProduct->only(['name', 'description', 'specifications', 'images_payload'])));
        $this->assertEquals($expectedNewHash, $freshShopProduct->master_version_hash);
    }

    /** @test */
    public function user_cannot_manage_products_for_a_shop_they_dont_have_permissions_for()
    {
        $this->actingAs($this->anotherShopOwner, 'sanctum'); // Owns otherShop, not testShop

        // Try to list available master products for testShop
        $response = $this->getJson(route('ijicommerce.api.shops.shop-products.available-master.index', $this->testShop));
        $response->assertStatus(403);

        // Try to add product to testShop
        $listingData = ['master_product_id' => $this->masterProduct1->id, 'price' => 10, 'stock_quantity' => 5];
        $responseStore = $this->postJson(route('ijicommerce.api.shops.shop-products.store', $this->testShop), $listingData);
        $responseStore->assertStatus(403);
    }
}
