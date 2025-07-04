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
        $tableName = config('ijiusersettings.table_name', 'user_settings');
        $userModelTable = app(config('ijiusersettings.user_model', config('auth.providers.users.model', \App\Models\User::class)))->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->foreignId('user_id')->constrained($userModelTable)->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('group')->nullable()->index();
            $table->string('type')->default('string')->comment('Helps with casting: string, integer, boolean, json, array, encrypted_string');
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijiusersettings.table_name', 'user_settings');
        Schema::dropIfExists($tableName);
    }
};
