<?php

use IJIDeals\AuctionSystem\Models\Auction;
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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            // Assuming product_id refers to a product in ijideals/commerce or similar
            // This might need to be a morphs('auctionable') if auctions can be for different item types.
            // For now, let's assume a product_id from a 'products' table.
            $table->unsignedBigInteger('product_id')->index();
            // $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete(); // Add if 'products' table exists

            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete(); // User from user-management

            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('starting_price', 15, 2);
            $table->decimal('current_price', 15, 2)->nullable();
            $table->decimal('reserve_price', 15, 2)->nullable(); // Minimum price to sell
            $table->decimal('bid_increment_amount', 10, 2)->default(1.00); // Minimum amount for next bid

            $table->timestamp('start_date')->index();
            $table->timestamp('end_date')->index();

            $table->string('status')->default('pending')->index(); // e.g., pending, active, ended_sold, ended_no_winner, ended_reserve_not_met, cancelled

            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('winning_bid_amount', 15, 2)->nullable();

            $table->boolean('auto_extend_on_bid')->default(false); // Anti-sniping: extend end_date if bid is placed near end
            $table->unsignedInteger('extension_time_minutes')->default(5); // How many minutes to extend by
            $table->unsignedInteger('bids_count')->default(0);

            $table->json('settings')->nullable(); // For future settings like type of auction (english, sealed_bid, etc.)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
