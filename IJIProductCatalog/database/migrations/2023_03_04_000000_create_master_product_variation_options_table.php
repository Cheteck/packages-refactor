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
        $pivotTable = config('ijiproductcatalog.tables.master_product_variation_options', 'master_product_variation_options');
        $variationsTable = config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');
        $attributeValuesTable = config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values');

        Schema::create($pivotTable, function (Blueprint $table) use ($variationsTable, $attributeValuesTable) {
            $table->id();

            $table->foreignId('master_product_variation_id')
                  ->constrained($variationsTable)
                  ->onDelete('cascade')
                  ->name('mpvo_variation_foreign');

            $table->foreignId('product_attribute_value_id')
                  ->constrained($attributeValuesTable)
                  ->onDelete('cascade')
                  ->name('mpvo_attribute_value_foreign');

            $table->timestamps();

            $table->unique(['master_product_variation_id', 'product_attribute_value_id'], 'mpvo_variation_attribute_value_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijiproductcatalog.tables.master_product_variation_options', 'master_product_variation_options');
        Schema::dropIfExists($tableName);
    }
};
