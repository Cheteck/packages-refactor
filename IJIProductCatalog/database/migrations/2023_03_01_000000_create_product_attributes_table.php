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
        $tableName = config('ijiproductcatalog.tables.product_attributes', 'product_attributes');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default('select');
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
        $tableName = config('ijiproductcatalog.tables.product_attributes', 'product_attributes');
        Schema::dropIfExists($tableName);
    }
};
