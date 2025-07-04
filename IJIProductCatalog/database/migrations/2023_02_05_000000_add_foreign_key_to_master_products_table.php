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
        $productProposalsTable = config('ijiproductcatalog.tables.product_proposals', 'product_proposals');

        Schema::table($masterProductsTable, function (Blueprint $table) use ($productProposalsTable) {
            $table->foreignId('created_by_proposal_id')->nullable()->constrained($productProposalsTable)->onDelete('set null');
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

        Schema::table($masterProductsTable, function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_proposal_id');
        });
    }
};
