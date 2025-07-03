<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\Order;
use IJIDeals\IJICommerce\Models\OrderItem;
use IJIDeals\IJICommerce\Models\ShopProduct;
use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Models\MasterProductVariation;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $order = Order::factory()->create();
        $shopProduct = ShopProduct::factory()->create(); // Assuming this is a simple product
        $masterProduct = $shopProduct->masterProduct; // Get the master product from shop product

        $data = [
            'order_id' => $order->id,
            'shop_product_id' => $shopProduct->id,
            'master_product_id' => $masterProduct->id,
            'product_name_at_purchase' => $masterProduct->name,
            'sku_at_purchase' => 'SKU123',
            'variant_details_at_purchase' => null, // For simple product
            'quantity' => 2,
            'price_at_purchase' => 49.99,
            'total_line_amount' => 99.98,
        ];
        $orderItem = OrderItem::create($data);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals($order->id, $orderItem->order_id);
        $this->assertEquals($shopProduct->id, $orderItem->shop_product_id);
        $this->assertEquals($masterProduct->id, $orderItem->master_product_id);
        $this->assertEquals(2, $orderItem->quantity);
    }

    /** @test */
    public function it_can_be_created_for_a_product_variation()
    {
        $order = Order::factory()->create();
        $shopProduct = ShopProduct::factory()->create(); // Parent ShopProduct
        $masterVariation = MasterProductVariation::factory()->create(['master_product_id' => $shopProduct->master_product_id]);
        // ShopProductVariation record would also exist, linking $shopProduct to $masterVariation
        // For simplicity, we're focusing on FKs here.

        $data = [
            'order_id' => $order->id,
            'shop_product_id' => $shopProduct->id, // Still link to parent ShopProduct
            'master_product_variation_id' => $masterVariation->id,
            'master_product_id' => $masterVariation->master_product_id,
            'product_name_at_purchase' => $masterVariation->masterProduct->name,
            'sku_at_purchase' => $masterVariation->sku,
            'variant_details_at_purchase' => ['Color' => 'Red', 'Size' => 'M'],
            'quantity' => 1,
            'price_at_purchase' => 55.00,
            'total_line_amount' => 55.00,
        ];
        $orderItem = OrderItem::create($data);
        $this->assertEquals($masterVariation->id, $orderItem->master_product_variation_id);
        $this->assertEquals(['Color' => 'Red', 'Size' => 'M'], $orderItem->variant_details_at_purchase);
    }


    /** @test */
    public function it_belongs_to_an_order()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $this->assertInstanceOf(Order::class, $orderItem->order);
    }

    /** @test */
    public function it_can_belong_to_a_shop_product()
    {
        $shopProduct = ShopProduct::factory()->create();
        $orderItem = OrderItem::factory()->create(['shop_product_id' => $shopProduct->id]);
        $this->assertInstanceOf(ShopProduct::class, $orderItem->shopProduct);
    }

    /** @test */
    public function it_belongs_to_a_master_product()
    {
        $masterProduct = MasterProduct::factory()->create();
        $orderItem = OrderItem::factory()->create(['master_product_id' => $masterProduct->id]);
        $this->assertInstanceOf(MasterProduct::class, $orderItem->masterProduct);
    }

    /** @test */
    public function it_can_belong_to_a_master_product_variation()
    {
        $masterVariation = MasterProductVariation::factory()->create();
        $orderItem = OrderItem::factory()->create(['master_product_variation_id' => $masterVariation->id]);
        $this->assertInstanceOf(MasterProductVariation::class, $orderItem->masterProductVariation);
    }
}

// Factories needed: OrderItemFactory (and its dependencies)
// namespace Database\Factories;
// use IJIDeals\IJICommerce\Models\OrderItem;
// class OrderItemFactory extends Factory {
//  protected $model = OrderItem::class;
//  public function definition() {
//      $masterProduct = MasterProduct::factory()->create();
//      return [
//          'order_id' => Order::factory(),
//          'master_product_id' => $masterProduct->id,
//          'product_name_at_purchase' => $masterProduct->name,
//          'quantity' => $this->faker->numberBetween(1,3),
//          'price_at_purchase' => $this->faker->randomFloat(2,10,100),
//          // total_line_amount would be calculated
//      ];
//  }
// }
