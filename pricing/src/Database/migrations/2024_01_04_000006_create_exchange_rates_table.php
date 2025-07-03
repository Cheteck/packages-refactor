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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency_code', 3);
            $table->string('to_currency_code', 3);
            $table->foreign('from_currency_code')->references('code')->on('currencies')->onDelete('cascade');
            $table->foreign('to_currency_code')->references('code')->on('currencies')->onDelete('cascade');
            $table->decimal('rate', 15, 6); // Sufficient precision for exchange rates
            $table->timestamp('fetched_at')->nullable(); // When the rate was last fetched/updated
            $table->timestamps();

            $table->unique(['from_currency_code', 'to_currency_code'], 'from_to_currency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
