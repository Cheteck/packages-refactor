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
        $valuesTable = config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values');
        $attributesTable = config('ijiproductcatalog.tables.product_attributes', 'product_attributes');

        Schema::create($valuesTable, function (Blueprint $table) use ($attributesTable) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained($attributesTable)->onDelete('cascade');
            $table->string('value');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['product_attribute_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values');
        Schema::dropIfExists($tableName);
    }
};
