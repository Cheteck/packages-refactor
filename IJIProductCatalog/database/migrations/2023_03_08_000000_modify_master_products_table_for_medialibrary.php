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
        $tableName = config('ijiproductcatalog.tables.master_products', 'master_products');

        Schema::table($tableName, function (Blueprint $table) {
            if (Schema::hasColumn($tableName, 'images_payload')) {
                $table->dropColumn('images_payload');
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
        $tableName = config('ijiproductcatalog.tables.master_products', 'master_products');

        Schema::table($tableName, function (Blueprint $table) {
            if (!Schema::hasColumn($tableName, 'images_payload')) {
                $table->json('images_payload')->nullable()->after('specifications');
            }
        });
    }
};
