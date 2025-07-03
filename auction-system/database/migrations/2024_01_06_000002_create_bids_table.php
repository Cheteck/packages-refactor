<?php

use IJIDeals\AuctionSystem\Models\Bid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; // For default status

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // User from user-management

            $table->decimal('amount', 15, 2);
            $table->boolean('is_auto_bid')->default(false); // Proxy bid / auto bid
            $table->decimal('max_auto_bid_amount', 15, 2)->nullable(); // Max amount for auto-bidding

            $table->string('status')->default('active')->index(); // e.g., active, outbid, winning, winner, cancelled

            $table->boolean('is_winning')->default(false)->index(); // Convenience flag, though status should be source of truth

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // A user cannot have multiple active bids on the same auction at the same amount.
            // $table->unique(['auction_id', 'user_id', 'amount']); // This might be too restrictive if bids can be placed and cancelled.
            // A user can only have one *current highest* active bid. This is usually handled by logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
