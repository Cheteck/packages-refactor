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
        $orderItemsTable = config('ijiordermanagement.tables.order_items', 'order_items');
        $ordersTable = config('ijiordermanagement.tables.orders', 'orders');
        $shopProductsTable = config('ijishoplistings.tables.shop_products', 'shop_products');
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');
        $mpvTable = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');

        Schema::create($orderItemsTable, function (Blueprint $table) use ($ordersTable, $shopProductsTable, $masterProductsTable, $mpvTable) {
            $table->id();
            $table->foreignId('order_id')->constrained($ordersTable)->onDelete('cascade');

            $table->foreignId('shop_product_id')->nullable()->constrained($shopProductsTable)->onDelete('set null');
            $table->foreignId('master_product_variation_id')->nullable()->constrained($mpvTable)->onDelete('set null');
            $table->foreignId('master_product_id')->constrained($masterProductsTable)->onDelete('restrict');

            $table->string('product_name_at_purchase');
            $table->string('sku_at_purchase')->nullable();
            $table->json('variant_details_at_purchase')->nullable();

            $table->unsignedInteger('quantity');
            $table->decimal('price_at_purchase', 10, 2);
            $table->decimal('total_line_amount', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $orderItemsTable = config('ijiordermanagement.tables.order_items', 'order_items');
        Schema::dropIfExists($orderItemsTable);
    }
};
