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
            if (Schema::hasColumn($tableName, 'shop_images_payload')) {
                $table->dropColumn('shop_images_payload');
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
        $tableName = config('ijishoplistings.tables.shop_products', 'shop_products');

        Schema::table($tableName, function (Blueprint $table) {
            if (!Schema::hasColumn($tableName, 'shop_images_payload')) {
                $table->json('shop_images_payload')->nullable()->after('shop_specific_notes');
            }
        });
    }
};
