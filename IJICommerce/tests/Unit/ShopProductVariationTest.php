<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Models\MasterProductVariation;
use IJIDeals\IJICommerce\Models\ShopProductVariation;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopProductVariationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $shopProduct = ShopProduct::factory()->create();
        $masterVariation = MasterProductVariation::factory()->create(['master_product_id' => $shopProduct->master_product_id]);

        $data = [
            'shop_product_id' => $shopProduct->id,
            'master_product_variation_id' => $masterVariation->id,
            'price' => 99.99,
            'stock_quantity' => 10,
            'shop_sku_variant' => 'SHOP-VAR-001',
            'sale_price' => 79.99,
            'sale_start_date' => now()->subDay(),
            'sale_end_date' => now()->addDays(7),
        ];
        $shopVariation = ShopProductVariation::create($data);

        $this->assertInstanceOf(ShopProductVariation::class, $shopVariation);
        $this->assertEquals($shopProduct->id, $shopVariation->shop_product_id);
        $this->assertEquals($masterVariation->id, $shopVariation->master_product_variation_id);
        $this->assertEquals(99.99, $shopVariation->price);
        $this->assertEquals(79.99, $shopVariation->sale_price);
    }

    /** @test */
    public function it_belongs_to_a_shop_product()
    {
        $shopProduct = ShopProduct::factory()->create();
        $shopVariation = ShopProductVariation::factory()->create(['shop_product_id' => $shopProduct->id]);
        $this->assertInstanceOf(ShopProduct::class, $shopVariation->shopProduct);
    }

    /** @test */
    public function it_belongs_to_a_master_product_variation()
    {
        $masterVariation = MasterProductVariation::factory()->create();
        $shopVariation = ShopProductVariation::factory()->create(['master_product_variation_id' => $masterVariation->id]);
        $this->assertInstanceOf(MasterProductVariation::class, $shopVariation->masterProductVariation);
    }

    /** @test */
    public function effective_price_accessor_works()
    {
        $shopVariation = ShopProductVariation::factory()->create([
            'price' => 50.00,
            'sale_price' => 40.00,
            'sale_start_date' => now()->subHour(),
            'sale_end_date' => now()->addHour(),
        ]);
        $this->assertEquals(40.00, $shopVariation->effective_price);
        $this->assertTrue($shopVariation->is_on_sale);

        $shopVariationNoSale = ShopProductVariation::factory()->create(['price' => 50.00, 'sale_price' => null]);
        $this->assertEquals(50.00, $shopVariationNoSale->effective_price);
        $this->assertFalse($shopVariationNoSale->is_on_sale);
    }
}

// Factories needed: ShopProductVariationFactory (and its dependencies like ShopFactory, MasterProductFactory, MasterProductVariationFactory)
// namespace Database\Factories;
// use IJIDeals\IJICommerce\Models\ShopProductVariation;
// class ShopProductVariationFactory extends Factory {
//  protected $model = ShopProductVariation::class;
//  public function definition() {
//      return [
//          'shop_product_id' => ShopProduct::factory(),
//          'master_product_variation_id' => MasterProductVariation::factory(), // Ensure this factory links to the same master_product as ShopProduct's
//          'price' => $this->faker->randomFloat(2, 5, 500),
//          'stock_quantity' => $this->faker->numberBetween(0,50)
//      ];
//  }
// }
