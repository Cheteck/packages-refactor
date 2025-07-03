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
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_id')->constrained('prices')->onDelete('cascade');
            $table->decimal('old_amount', 15, 4);
            $table->decimal('new_amount', 15, 4);
            $table->timestamp('changed_at')->useCurrent();
            $table->foreignId('user_id')->nullable()->comment('User who made the change')->constrained(config('pricing.user_table_name', 'users'))->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
