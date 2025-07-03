<?php

namespace IJIDeals\Inventory\Database\factories;

use IJIDeals\Inventory\Models\Inventory;
use IJIDeals\Inventory\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

// Placeholder for a Stockable model - replace with actual models from commerce/catalog
// use IJIDeals\IJICommerce\Models\Product;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        // You'll need a way to create or get an existing stockable item (e.g., Product)
        // For now, let's assume you might pass it in or use a placeholder.
        // $stockable = Product::factory()->create();

        return [
            // 'stockable_id' => $stockable->id,
            // 'stockable_type' => get_class($stockable),
            'stockable_id' => $this->faker->randomNumber(), // Placeholder
            'stockable_type' => 'App\\Models\\Product', // Placeholder, replace with actual
            'location_id' => InventoryLocation::factory(),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'reserved_quantity' => $this->faker->numberBetween(0, 50),
            'last_stock_update' => $this->faker->dateTimeThisMonth(),
        ];
    }

    // Example of how to specify a stockable model
    // public function forStockable(Model $stockable)
    // {
    //     return $this->state([
    //         'stockable_id' => $stockable->getKey(),
    //         'stockable_type' => $stockable->getMorphClass(),
    //     ]);
    // }
}
