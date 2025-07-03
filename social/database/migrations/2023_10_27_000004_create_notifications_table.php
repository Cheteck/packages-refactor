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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Standard for Laravel notifications
            // $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Replaced by polymorphic notifiable
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type'); // e.g., 'new_like', 'new_comment', 'new_follower'
            $table->json('data'); // Renamed from contenu, changed to json
            $table->timestamp('read_at')->nullable(); // Renamed from lu, type changed
            $table->timestamps();

            $table->index(['notifiable_id', 'notifiable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'notifiable_id') && Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->dropIndex(['notifiable_id', 'notifiable_type']);
            }
        });
        Schema::dropIfExists('notifications');
    }
};
