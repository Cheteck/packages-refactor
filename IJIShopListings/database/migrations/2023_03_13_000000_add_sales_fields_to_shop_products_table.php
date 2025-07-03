<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('ijishoplistings.tables.shop_products', 'shop_products');

        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('price');
            $table->timestamp('sale_start_date')->nullable()->after('sale_price');
            $table->timestamp('sale_end_date')->nullable()->after('sale_start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijishoplistings.tables.shop_products', 'shop_products');
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'sale_start_date', 'sale_end_date']);
        });
    }
};
