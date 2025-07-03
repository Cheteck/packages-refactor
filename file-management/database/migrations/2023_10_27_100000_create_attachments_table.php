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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id(); // bigIncrements by default
            $table->morphs('attachable'); // Creates attachable_id (unsignedBigInteger) and attachable_type (string)

            // Assuming the 'users' table uses bigIncrements for its ID.
            // If users table uses UUIDs, user_id should be $table->uuid('user_id')
            // and constrained accordingly if foreign key is desired (though direct FK to UUID needs careful setup).
            // For simplicity, assuming standard bigIncrements for users.id here.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('disk');
            $table->string('filepath'); // Consider making this unique if filenames are not inherently unique (e.g., hashed)
            $table->string('filename');
            $table->string('mimetype')->nullable();
            $table->unsignedInteger('size_bytes');
            $table->string('type')->default('unknown'); // General category
            $table->json('metadata')->nullable();

            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
