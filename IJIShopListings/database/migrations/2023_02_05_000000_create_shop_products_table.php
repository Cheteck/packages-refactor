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
        $shopProductsTable = config('ijishoplistings.tables.shop_products', 'shop_products');
        $shopsTable = config('ijicommerce.tables.shops', 'shops'); // This table remains in the IJICommerce package
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');

        Schema::create($shopProductsTable, function (Blueprint $table) use ($shopsTable, $masterProductsTable) {
            $table->id();
            $table->foreignId('shop_id')->constrained($shopsTable)->onDelete('cascade');
            $table->foreignId('master_product_id')->constrained($masterProductsTable)->onDelete('cascade');

            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_visible_in_shop')->default(true);

            $table->text('shop_specific_notes')->nullable();
            $table->json('shop_images_payload')->nullable();

            $table->string('master_version_hash')->nullable();
            $table->boolean('needs_review_by_shop')->default(false);

            $table->timestamps();

            $table->unique(['shop_id', 'master_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $shopProductsTable = config('ijishoplistings.tables.shop_products', 'shop_products');
        Schema::dropIfExists($shopProductsTable);
    }
};
