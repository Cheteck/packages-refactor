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
        $tableName = config('ijicommerce.tables.shops', 'shops'); // Example if table names are configurable

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            // 'owner_id' was removed - ownership via Spatie roles on the Shop (team) model
            // $table->foreignId('owner_id')->constrained(config('ijicommerce.tables.users', 'users'))->onDelete('cascade');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_photo_path')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website_url')->nullable();

            $table->string('status')->default('pending_approval'); // e.g., active, inactive, pending_approval, suspended

            $table->json('settings')->nullable(); // For shop-specific settings

            $table->timestamp('approved_at')->nullable(); // If moderation is in place

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            $table->text('display_address')->nullable(); // General text field for address

            $table->timestamps();
            // $table->softDeletes(); // Uncomment if using SoftDeletes trait in Shop model
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('ijicommerce.tables.shops', 'shops');
        Schema::dropIfExists($tableName);
    }
};
