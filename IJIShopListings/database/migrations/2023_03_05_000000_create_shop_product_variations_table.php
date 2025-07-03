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
        $spvTable = config('ijishoplistings.tables.shop_product_variations', 'shop_product_variations');
        $shopProductsTable = config('ijishoplistings.tables.shop_products', 'shop_products');
        $mpvTable = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');

        Schema::create($spvTable, function (Blueprint $table) use ($shopProductsTable, $mpvTable) {
            $table->id();

            $table->foreignId('shop_product_id')
                  ->constrained($shopProductsTable)
                  ->onDelete('cascade');

            $table->foreignId('master_product_variation_id')
                  ->constrained($mpvTable)
                  ->onDelete('cascade');

            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->string('shop_sku_variant')->nullable();

            $table->timestamps();

            $table->unique(['shop_product_id', 'master_product_variation_id'], 'spv_shop_master_variation_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijishoplistings.tables.shop_product_variations', 'shop_product_variations');
        Schema::dropIfExists($tableName);
    }
};
