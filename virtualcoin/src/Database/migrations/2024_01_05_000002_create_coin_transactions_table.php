<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_coin_id')->constrained('virtual_coins')->cascadeOnDelete(); // Link to the wallet
            // OR directly user_id if you prefer, but linking to wallet is cleaner if wallet has more properties than just balance.
            // $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 15, 4); // Positive for deposit/credit, negative for withdrawal/debit
            $table->string('type')->index(); // e.g., 'deposit', 'withdrawal', 'spend_sponsorship', 'earn_reward', 'refund'
            $table->string('status')->default('completed')->index(); // e.g., 'pending', 'completed', 'failed', 'cancelled'

            $table->string('reference')->nullable()->unique(); // Unique reference for the transaction (e.g., order_id, payment_gateway_txn_id, internal_ref)
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For storing related IDs like order_id, post_id, etc.

            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);

            $table->timestamp('created_at')->useCurrent();
            // No 'updated_at' for immutable transaction logs typically.
            // $table->softDeletes(); // Usually not soft deleted, but depends on requirements.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
