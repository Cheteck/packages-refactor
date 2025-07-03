<?php

namespace IJIDeals\AuctionSystem\Database\Factories;

use IJIDeals\AuctionSystem\Enums\AuctionStatusEnum;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\IJICommerce\Models\ijicommerce\Product;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'starting_price' => $this->faker->randomFloat(2, 10, 100),
            'current_price' => null,
            'reserve_price' => $this->faker->randomFloat(2, 50, 200),
            'increment_amount' => $this->faker->randomFloat(2, 1, 10),
            'start_date' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'end_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'status' => $this->faker->randomElement(AuctionStatusEnum::cases()),
            'winner_id' => null,
            'auto_extend' => $this->faker->boolean,
            'extension_time' => $this->faker->numberBetween(5, 15),
            'min_bids' => $this->faker->numberBetween(0, 5),
            'featured' => $this->faker->boolean,
            'description' => $this->faker->paragraph,
        ];
    }

    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AuctionStatusEnum::ACTIVE,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(7),
            ];
        });
    }

    public function ended(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AuctionStatusEnum::ENDED,
                'end_date' => now()->subDay(),
            ];
        });
    }

    public function withWinner(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'winner_id' => User::factory(),
                'status' => AuctionStatusEnum::SOLD,
                'current_price' => $this->faker->randomFloat(2, $attributes['reserve_price'] ?? 50, 300),
            ];
        });
    }
}
