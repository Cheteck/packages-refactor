<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\Order;
use IJIDeals\IJICommerce\Models\Shop;
use App\Models\User; // Test User model
use IJIDeals\IJICommerce\Models\OrderItem;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes_and_generates_order_number()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(); // Assuming App\Models\User factory

        $data = [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            // 'order_number' will be auto-generated
            'total_amount' => 150.75,
            'currency' => 'EUR',
            'billing_address' => ['city' => 'Testville', 'street' => '123 Main St'],
            'shipping_address' => ['city' => 'Shipville', 'street' => '456 Ocean Ave'],
            'payment_method' => 'stripe_cc',
            'payment_status' => 'pending',
            'status' => 'pending_payment',
        ];
        $order = Order::create($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->order_number);
        $this->assertTrue(Str::startsWith($order->order_number, 'ORD-'));
        $this->assertEquals($shop->id, $order->shop_id);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(150.75, $order->total_amount);
        $this->assertEquals('pending_payment', $order->status);
    }

    /** @test */
    public function it_belongs_to_a_shop()
    {
        $shop = Shop::factory()->create();
        $order = Order::factory()->create(['shop_id' => $shop->id]);
        $this->assertInstanceOf(Shop::class, $order->shop);
    }

    /** @test */
    public function it_belongs_to_a_user_customer()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(User::class, $order->user);
    }

    /** @test */
    public function it_can_have_many_order_items()
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]); // Assuming OrderItemFactory

        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }

    /** @test */
    public function it_casts_addresses_to_array()
    {
        $order = Order::factory()->create([
            'billing_address' => ['city' => 'Billing City'],
            'shipping_address' => ['city' => 'Shipping City'],
        ]);
        $this->assertIsArray($order->billing_address);
        $this->assertIsArray($order->shipping_address);
        $this->assertEquals('Billing City', $order->billing_address['city']);
    }
}

// Factories: OrderFactory, OrderItemFactory
// namespace Database\Factories;
// use IJIDeals\IJICommerce\Models\Order;
// class OrderFactory extends Factory { protected $model = Order::class; public function definition() { return ['shop_id' => Shop::factory(), 'user_id' => User::factory(), 'total_amount' => $this->faker->randomFloat(2,20,500), 'currency' => 'USD']; } }

// use IJIDeals\IJICommerce\Models\OrderItem;
// class OrderItemFactory extends Factory { protected $model = OrderItem::class; public function definition() { /* ... */ } }
