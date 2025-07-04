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
        $tableName = config('ijiproductcatalog.tables.brands', 'brands');

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn($tableName, 'cover_photo_path')) {
                $table->dropColumn('cover_photo_path');
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
        $tableName = config('ijiproductcatalog.tables.brands', 'brands');

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'logo_path')) {
                $table->string('logo_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn($tableName, 'cover_photo_path')) {
                $table->string('cover_photo_path')->nullable()->after('logo_path');
            }
        });
    }
};
