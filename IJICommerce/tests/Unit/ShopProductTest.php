<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $shop = Shop::factory()->create(); // Assuming factories are set up
        $masterProduct = MasterProduct::factory()->create();

        $data = [
            'shop_id' => $shop->id,
            'master_product_id' => $masterProduct->id,
            'price' => 199.99,
            'stock_quantity' => 50,
            'is_visible_in_shop' => true,
            'shop_specific_notes' => 'Special edition for our shop.',
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ShopProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $shop = Shop::factory()->create(); // Assuming factories are set up
        $masterProduct = MasterProduct::factory()->create();

        $data = [
            'shop_id' => $shop->id,
            'master_product_id' => $masterProduct->id,
            'price' => 199.99,
            'stock_quantity' => 50,
            'is_visible_in_shop' => true,
            'shop_specific_notes' => 'Special edition for our shop.',
            'master_version_hash' => 'somehash123',
            'needs_review_by_shop' => false,
            // 'shop_images_payload' removed
            'sale_price' => 179.99,
            'sale_start_date' => now()->subDay(),
            'sale_end_date' => now()->addWeek(),
        ];
        $shopProduct = ShopProduct::create($data);

        $this->assertInstanceOf(ShopProduct::class, $shopProduct);
        $this->assertEquals($shop->id, $shopProduct->shop_id);
        $this->assertEquals($masterProduct->id, $shopProduct->master_product_id);
        $this->assertEquals(199.99, $shopProduct->price); // Floats might need delta comparison
        $this->assertEquals(50, $shopProduct->stock_quantity);
        $this->assertTrue($shopProduct->is_visible_in_shop);
    }

    /** @test */
    public function it_belongs_to_a_shop()
    {
        $shop = Shop::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['shop_id' => $shop->id]);
        $this->assertInstanceOf(Shop::class, $shopProduct->shop);
    }

    /** @test */
    public function it_belongs_to_a_master_product()
    {
        $masterProduct = MasterProduct::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['master_product_id' => $masterProduct->id]);
        $this->assertInstanceOf(MasterProduct::class, $shopProduct->masterProduct);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $shopProduct = ShopProduct::factory()->create([
            'price' => 99.50,
            'stock_quantity' => 10,
            'is_visible_in_shop' => 1,
            'needs_review_by_shop' => 0,
            // 'shop_images_payload' => ['/path.jpg'] // Removed
            'sale_price' => 89.99,
            'sale_start_date' => now(),
            'sale_end_date' => now()->addDays(5),
        ]);

        $this->assertIsFloat($shopProduct->price);
        $this->assertEquals(99.50, $shopProduct->price);
        $this->assertIsInt($shopProduct->stock_quantity);
        $this->assertIsBool($shopProduct->is_visible_in_shop);
        $this->assertTrue($shopProduct->is_visible_in_shop);
        $this->assertIsBool($shopProduct->needs_review_by_shop);
        $this->assertFalse($shopProduct->needs_review_by_shop);
        // $this->assertIsArray($shopProduct->shop_images_payload); // Removed
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $shopProduct->sale_start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $shopProduct->sale_end_date);
    }

    /** @test */
    public function it_can_have_additional_images_via_medialibrary()
    {
        $shopProduct = ShopProduct::factory()->create();
        $fakeImage = UploadedFile::fake()->image('shop_img.jpg');

        $shopProduct->addMedia($fakeImage)->toMediaCollection(config('ijicommerce.media_library_collections.shop_product_additional_images', 'shop_product_additional_images'));

        $this->assertTrue($shopProduct->hasMedia(config('ijicommerce.media_library_collections.shop_product_additional_images', 'shop_product_additional_images')));
    }

    /** @test */
    public function effective_price_accessor_works_correctly()
    {
        $shopProduct = ShopProduct::factory()->create([
            'price' => 100.00,
            'sale_price' => 80.00,
            'sale_start_date' => now()->subDay(),
            'sale_end_date' => now()->addDay(),
        ]);
        $this->assertEquals(80.00, $shopProduct->effective_price);
        $this->assertTrue($shopProduct->is_on_sale);

        $shopProductNoSale = ShopProduct::factory()->create(['price' => 100.00, 'sale_price' => null]);
        $this->assertEquals(100.00, $shopProductNoSale->effective_price);
        $this->assertFalse($shopProductNoSale->is_on_sale);

        $shopProductFutureSale = ShopProduct::factory()->create([
            'price' => 100.00, 'sale_price' => 70.00,
            'sale_start_date' => now()->addDay(),
            'sale_end_date' => now()->addDays(2)
        ]);
        $this->assertEquals(100.00, $shopProductFutureSale->effective_price);
        $this->assertFalse($shopProductFutureSale->is_on_sale);

        $shopProductExpiredSale = ShopProduct::factory()->create([
            'price' => 100.00, 'sale_price' => 60.00,
            'sale_start_date' => now()->subDays(2),
            'sale_end_date' => now()->subDay()
        ]);
        $this->assertEquals(100.00, $shopProductExpiredSale->effective_price);
        $this->assertFalse($shopProductExpiredSale->is_on_sale);
    }
}

// Required Factories (simplified stubs, place in database/factories for your testbench app or package):
// namespace Database\Factories;
// use Illuminate\Database\Eloquent\Factories\Factory;
// use IJIDeals\IJICommerce\Models\ShopProduct;
// use IJIDeals\IJICommerce\Models\Shop;
// use IJIDeals\IJICommerce\Models\MasterProduct;
// class ShopProductFactory extends Factory {
//  protected $model = ShopProduct::class;
//  public function definition() {
//      return [
//          'shop_id' => Shop::factory(),
//          'master_product_id' => MasterProduct::factory(),
//          'price' => $this->faker->randomFloat(2, 10, 1000),
//          'stock_quantity' => $this->faker->numberBetween(0,100),
//          'is_visible_in_shop' => true
//      ];
//  }
// }
// (Ensure ShopFactory and MasterProductFactory also exist)
