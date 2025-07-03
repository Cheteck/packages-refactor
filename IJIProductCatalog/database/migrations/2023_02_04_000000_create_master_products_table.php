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
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');
        $brandsTable = config('ijiproductcatalog.tables.brands', 'brands');
        $categoriesTable = config('ijiproductcatalog.tables.categories', 'categories');
        $productProposalsTable = config('ijiproductcatalog.tables.product_proposals', 'product_proposals');

        Schema::create($masterProductsTable, function (Blueprint $table) use ($brandsTable, $categoriesTable, $productProposalsTable) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->foreignId('brand_id')->nullable()->constrained($brandsTable)->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained($categoriesTable)->onDelete('set null');

            $table->json('specifications')->nullable();
            $table->json('images_payload')->nullable();

            $table->string('status')->default('draft_by_admin');

            $table->foreignId('created_by_proposal_id')->nullable()->constrained($productProposalsTable)->onDelete('set null');

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
        $masterProductsTable = config('ijiproductcatalog.tables.master_products', 'master_products');
        Schema::dropIfExists($masterProductsTable);
    }
};
