<?php

namespace IJIDeals\IJICommerce\Tests\Feature\Admin;

use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Models\Brand;
use IJIDeals\IJICommerce\Models\Category;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Event; // For event testing

class AdminMasterProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdminUser;
    protected Role $platformAdminRole;
    protected Brand $brand;
    protected Category $category;

    public function setUp(): void
    {
        parent::setUp();
        $this->platformAdminRole = Role::firstOrCreate(['name' => 'Platform Admin', 'guard_name' => 'web']);
        $this->platformAdminUser = $this->createUser(['email' => 'admin.masterprods@example.com']);
        $this->platformAdminUser->assignRole($this->platformAdminRole->name);

        $this->brand = Brand::create(['name' => 'Test Brand For Master']);
        $this->category = Category::create(['name' => 'Test Category For Master']);
    }

    /** @test */
    public function platform_admin_can_create_master_product()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $productData = [
            'name' => 'Admin Master Product',
            'description' => 'Directly created by admin.',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'specifications' => ['material' => 'steel'],
            'status' => 'active',
        ];

        $response = $this->postJson(route('ijicommerce.api.admin.master-products.store'), $productData);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Admin Master Product']);
        $this->assertDatabaseHas('master_products', ['name' => 'Admin Master Product', 'brand_id' => $this->brand->id]);
    }

    /** @test */
    public function platform_admin_can_update_master_product_and_it_notifies_shops()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $masterProduct = MasterProduct::create([
            'name' => 'Old MP Name', 'status' => 'active',
            'description' => 'Old Desc',
            'specifications' => ['color' => 'blue']
        ]);

        $shop = $this->createShop();
        $shopProduct = $shop->shopProducts()->create([
            'master_product_id' => $masterProduct->id,
            'price' => 50,
            'stock_quantity' => 10,
            'master_version_hash' => md5(serialize($masterProduct->only(['name', 'description', 'specifications', 'images_payload']))),
            'needs_review_by_shop' => false,
            'is_visible_in_shop' => true,
        ]);

        $updateData = [
            'name' => 'New MP Name',
            'description' => 'New Desc', // This is a significant change
            'status' => 'active', // Keep it active
        ];

        // Event::fake(\IJIDeals\IJICommerce\Events\MasterProductDetailsChangedForShop::class); // If using events

        $response = $this->putJson(route('ijicommerce.api.admin.master-products.update', $masterProduct->id), $updateData);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New MP Name']);

        $updatedShopProduct = $shopProduct->fresh();
        $this->assertTrue($updatedShopProduct->needs_review_by_shop);
        $this->assertFalse($updatedShopProduct->is_visible_in_shop);

        $expectedNewHash = md5(serialize($masterProduct->fresh()->only(['name', 'description', 'specifications', 'images_payload'])));
        $this->assertEquals($expectedNewHash, $updatedShopProduct->master_version_hash);

        // Event::assertDispatched(\IJIDeals\IJICommerce\Events\MasterProductDetailsChangedForShop::class, function ($event) use ($updatedShopProduct) {
        //     return $event->shopProduct->id === $updatedShopProduct->id;
        // });
    }

    /** @test */
    public function updating_non_significant_field_or_non_active_master_product_does_not_trigger_shop_product_review()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $masterProduct = MasterProduct::create([
            'name' => 'Stable MP Name', 'status' => 'draft_by_admin', // Not active
            'description' => 'Stable Desc',
            'specifications' => ['color' => 'red']
        ]);

        $shop = $this->createShop();
        $shopProduct = $shop->shopProducts()->create([
            'master_product_id' => $masterProduct->id,
            'price' => 60,
            'stock_quantity' => 5,
            'master_version_hash' => 'initial_hash',
            'needs_review_by_shop' => false,
            'is_visible_in_shop' => true,
        ]);

        $updateData = [
            // 'name' => 'Still Stable MP Name', // No change to significant fields
            'status' => 'draft_by_admin', // Still not active
        ];
        // Also test if only status changes from active to draft, it should not trigger review.
        // The trigger is for changes to user-facing data of an *active* product.

        $response = $this->putJson(route('ijicommerce.api.admin.master-products.update', $masterProduct->id), $updateData);
        $response->assertStatus(200);

        $updatedShopProduct = $shopProduct->fresh();
        $this->assertFalse($updatedShopProduct->needs_review_by_shop);
        $this->assertTrue($updatedShopProduct->is_visible_in_shop); // Visibility should not change
        $this->assertEquals('initial_hash', $updatedShopProduct->master_version_hash); // Hash should not change
    }
}
