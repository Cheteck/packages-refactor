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
        $userModelTable = (new (config('messaging.user_model')))->getTable();

        Schema::create('messaging_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type')->default('individual')->comment('individual, group'); // individual, group
            $table->unsignedBigInteger('group_id')->nullable(); // Lié à messaging_groups
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('messaging_conversation_user', function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('messaging_conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on($userModelTable)->onDelete('cascade');

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messaging_groups', function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by_user_id')->references('id')->on($userModelTable);
        });

        // Clé étrangère pour group_id dans messaging_conversations après la création de messaging_groups
        Schema::table('messaging_conversations', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('messaging_groups')->onDelete('cascade');
        });

        Schema::create('messaging_group_members', function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member'); // admin, member
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('messaging_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on($userModelTable)->onDelete('cascade');

            $table->unique(['group_id', 'user_id']);
        });

        Schema::create('messaging_messages', function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('content'); // Encrypted content
            $table->string('type')->default('text')->comment('text, image, file, ephemeral_text, etc.');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->timestamp('expires_at')->nullable()->index(); // For ephemeral messages
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('messaging_conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on($userModelTable)->onDelete('cascade');
            $table->index('type');
        });

        Schema::create('messaging_message_recipients', function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id'); // Recipient user
            $table->unsignedBigInteger('conversation_id'); // Denormalized for easier querying
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable(); // Optional: if delivery status is tracked
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messaging_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on($userModelTable)->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('messaging_conversations')->onDelete('cascade');

            $table->unique(['message_id', 'user_id']);
            $table->index('read_at');
        });

        // The 'public_key' (and potentially 'private_key_encrypted')
        // is now expected to be part of the main user table,
        // as per user request. So, no 'messaging_user_keys' table here.
        // The application host will need to add these columns to their 'users' table.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messaging_message_recipients');
        Schema::dropIfExists('messaging_messages');
        Schema::dropIfExists('messaging_group_members');

        // Need to drop foreign key constraint before dropping table if it was added in a separate Schema::table call
        Schema::table('messaging_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('messaging_conversations', 'group_id')) { // Check if column exists
                 // SQLite does not support dropping foreign keys directly in older versions.
                 // For other DBs, this is fine. Consider this for broader DB support.
                if (DB::getDriverName() !== 'sqlite') { // DB facade might not be available here directly, consider alternative for driver check if needed
                    $table->dropForeign(['group_id']);
                }
            }
        });
        Schema::dropIfExists('messaging_groups');
        Schema::dropIfExists('messaging_conversation_user');
        Schema::dropIfExists('messaging_conversations');
    }
};
