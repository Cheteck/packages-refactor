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
        $variationsTable = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');

        Schema::create($variationsTable, function (Blueprint $table) use ($masterProductsTable) {
            $table->id();
            $table->foreignId('master_product_id')->constrained($masterProductsTable)->onDelete('cascade');

            $table->string('sku')->unique()->nullable();
            $table->decimal('price_adjustment', 10, 2)->nullable()->default(0.00);
            $table->integer('stock_override')->nullable();
            $table->json('images_payload_variation')->nullable();

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
        $tableName = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');
        Schema::dropIfExists($tableName);
    }
};
