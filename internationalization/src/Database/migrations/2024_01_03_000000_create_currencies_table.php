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
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary(); // e.g., USD, EUR, JPY
            $table->string('name');
            $table->string('symbol', 10);
            $table->unsignedSmallInteger('decimal_digits')->default(2);
            $table->decimal('exchange_rate', 16, 8)->default(1.00); // Exchange rate to a base currency (e.g., USD)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
