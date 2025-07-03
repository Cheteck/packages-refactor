<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('notifications-manager.table_names.user_notification_preferences', 'user_notification_preferences');
        $userModelTable = (new (config('notifications-manager.user_model', \App\Models\User::class)))->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->foreignId('user_id')->constrained($userModelTable)->onDelete('cascade');
            $table->string('notification_type')->index(); // Key of the notification type from config
            $table->string('channel')->index();           // Key of the channel from config
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type', 'channel'], 'user_notification_preference_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('notifications-manager.table_names.user_notification_preferences', 'user_notification_preferences');
        Schema::dropIfExists($tableName);
    }
};
