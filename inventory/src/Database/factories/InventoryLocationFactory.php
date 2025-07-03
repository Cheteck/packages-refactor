<?php

namespace IJIDeals\Inventory\Database\factories;

use IJIDeals\Inventory\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' Warehouse',
            'address_details' => [
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'postal_code' => $this->faker->postcode,
                'country' => $this->faker->countryCode,
            ],
            'is_active' => true,
        ];
    }
}
