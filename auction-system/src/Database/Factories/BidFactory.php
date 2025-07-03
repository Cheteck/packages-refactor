<?php

namespace IJIDeals\AuctionSystem\Database\Factories;

use IJIDeals\AuctionSystem\Enums\BidStatusEnum;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Models\Bid;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BidFactory extends Factory
{
    protected $model = Bid::class;

    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 200),
            'auto_bid' => $this->faker->boolean,
            'max_amount' => $this->faker->randomFloat(2, 200, 500),
            'outbid' => false,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'status' => $this->faker->randomElement(BidStatusEnum::cases()),
        ];
    }

    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BidStatusEnum::ACTIVE,
                'outbid' => false,
            ];
        });
    }

    public function winning(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BidStatusEnum::WINNING,
                'outbid' => false,
            ];
        });
    }

    public function outbid(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BidStatusEnum::OUTBID,
                'outbid' => true,
            ];
        });
    }
}
