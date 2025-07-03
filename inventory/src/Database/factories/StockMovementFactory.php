<?php

namespace IJIDeals\Inventory\Database\factories;

use IJIDeals\Inventory\Models\Inventory;
use IJIDeals\Inventory\Models\StockMovement;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // Assuming User model path

// Placeholder for a Stockable model - replace with actual models
// use IJIDeals\IJICommerce\Models\Product;
// use IJIDeals\IJICommerce\Models\Order;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        // $stockable = Product::factory()->create();
        $inventory = Inventory::factory()->create();
        // $reference = Order::factory()->create(); // Example reference

        $quantityChange = $this->faker->numberBetween(-100, 100);
        $quantityBefore = $inventory->quantity; // Or a random value if inventory is not pre-existing

        return [
            'stockable_id' => $inventory->stockable_id,
            'stockable_type' => $inventory->stockable_type,
            'inventory_id' => $inventory->id,
            'location_id' => $inventory->location_id,
            'user_id' => User::factory(), // Or null
            // 'reference_id' => $reference->id, // Placeholder
            // 'reference_type' => get_class($reference), // Placeholder
            'reference_id' => null,
            'reference_type' => null,
            'type' => $this->faker->randomElement(['restock', 'sale', 'adjustment', 'damage', 'return']),
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore + $quantityChange, // This would be updated by the service in real usage
            'description' => $this->faker->sentence,
            'created_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
