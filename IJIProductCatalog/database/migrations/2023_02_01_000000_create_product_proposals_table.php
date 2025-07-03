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
        $tableName = config('ijiproductcatalog.tables.product_proposals', 'product_proposals');
        $shopsTableName = config('ijicommerce.tables.shops', 'shops'); // This table remains in the IJICommerce package
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');

        Schema::create($tableName, function (Blueprint $table) use ($shopsTableName, $masterProductsTable) {
            $table->id();
            $table->foreignId('shop_id')->constrained($shopsTableName)->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('proposed_brand_name')->nullable();
            $table->string('proposed_category_name')->nullable();

            $table->json('proposed_specifications')->nullable();
            $table->json('proposed_images_payload')->nullable();
            $table->json('proposed_variations_payload')->nullable();

            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();

            $table->foreignId('approved_master_product_id')->nullable()
                  ->constrained($masterProductsTable)
                  ->onDelete('set null');

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
        $tableName = config('ijiproductcatalog.tables.product_proposals', 'product_proposals');
        Schema::dropIfExists($tableName);
    }
};
