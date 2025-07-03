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
        Schema::create('virtual_coins', function (Blueprint $table) { // Table for wallets
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete(); // Assuming 'users' table from user-management
            $table->decimal('balance', 15, 4)->default(0); // Store with sufficient precision
            $table->string('currency_code')->default('VC'); // Virtual Coin currency code, can be configurable
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_coins');
    }
};
