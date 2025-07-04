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
        $tableName = config('ijisettings.table_name', 'platform_settings');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string')->comment('Helps with casting: string, integer, boolean, json, array, encrypted_string');
            $table->string('group')->nullable()->index()->comment('For grouping settings in UI');
            $table->string('label')->nullable()->comment('Human-readable label for UI');
            $table->text('description')->nullable()->comment('Description for UI');
            $table->boolean('is_encrypted')->default(false);
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
        $tableName = config('ijisettings.table_name', 'platform_settings');
        Schema::dropIfExists($tableName);
    }
};
