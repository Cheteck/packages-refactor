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
        $tableName = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');

        Schema::table($tableName, function (Blueprint $table) {
            if (Schema::hasColumn($tableName, 'images_payload_variation')) {
                $table->dropColumn('images_payload_variation');
            }
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

        Schema::table($tableName, function (Blueprint $table) {
            if (!Schema::hasColumn($tableName, 'images_payload_variation')) {
                $table->json('images_payload_variation')->nullable()->after('stock_override');
            }
        });
    }
};
