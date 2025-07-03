<?php

use Illuminate\Support\Facades\Route;
use Acme\SecureMessaging\Http\Controllers\UserProfileController;

Route::middleware(config('messaging.routes.middleware', ['api']))
    ->prefix(config('messaging.routes.prefix', 'api/messaging'))
    ->group(function () {

        Route::middleware(config('messaging.routes.auth_middleware', 'auth:sanctum'))->group(function() {
            // User Profile
            Route::get('/profile', [UserProfileController::class, 'show'])->name('messaging.profile.show');
            Route::put('/profile', [UserProfileController::class, 'update'])->name('messaging.profile.update');
            Route::get('/users/{userId}/public-key', [UserProfileController::class, 'getUserPublicKey'])->name('messaging.users.publicKey');

            // Messages
            Route::post('/messages', [\Acme\SecureMessaging\Http\Controllers\MessageController::class, 'store'])
                ->middleware('throttle.messaging.send_message') // Apply rate limiter
                ->name('messaging.messages.store');
            // Route to get messages for a conversation
            Route::get('/conversations/{conversationUuid}/messages', [\Acme\SecureMessaging\Http\Controllers\MessageController::class, 'index'])->name('messaging.conversations.messages.index');
            Route::put('/messages/{messageId}/read', [\Acme\SecureMessaging\Http\Controllers\MessageController::class, 'markAsRead'])->name('messaging.messages.read');
            Route::delete('/messages/{messageId}', [\Acme\SecureMessaging\Http\Controllers\MessageController::class, 'destroy'])->name('messaging.messages.destroy');

            // Conversations
            Route::get('/conversations', [\Acme\SecureMessaging\Http\Controllers\ConversationController::class, 'index'])->name('messaging.conversations.index');
            Route::get('/conversations/{conversationUuid}', [\Acme\SecureMessaging\Http\Controllers\ConversationController::class, 'show'])->name('messaging.conversations.show');

            // Groups
            Route::post('/groups', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'store'])
                ->middleware('throttle.messaging.create_group') // Apply rate limiter
                ->name('messaging.groups.store');
            Route::get('/groups/{groupUuid}', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'show'])->name('messaging.groups.show');
            Route::put('/groups/{groupUuid}', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'update'])->name('messaging.groups.update');
            Route::delete('/groups/{groupUuid}', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'destroy'])->name('messaging.groups.destroy');
            Route::get('/groups/{groupUuid}/members', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'listMembers'])->name('messaging.groups.members.list');
            Route::post('/groups/{groupUuid}/members/{userIdToAdd}', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'addMember'])->name('messaging.groups.members.add');
            Route::delete('/groups/{groupUuid}/members/{userIdToRemove}', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'removeMember'])->name('messaging.groups.members.remove');
            Route::put('/groups/{groupUuid}/members/{memberIdToUpdate}/role', [\Acme\SecureMessaging\Http\Controllers\GroupController::class, 'updateMemberRole'])->name('messaging.groups.members.updateRole');

            // Typing Indicators
            Route::post('/conversations/{conversationUuid}/typing', [\Acme\SecureMessaging\Http\Controllers\TypingController::class, 'store'])->name('messaging.conversations.typing');

            // Attachments
            Route::post('/attachments', [\Acme\SecureMessaging\Http\Controllers\AttachmentController::class, 'store'])
                ->middleware('throttle.messaging.upload_attachment') // Apply rate limiter
                ->name('messaging.attachments.store');

        });

        // Publicly accessible route to get a user's public key if needed, even for non-authenticated users.
        // However, the current implementation of getUserPublicKey is under auth middleware.
        // If a truly public endpoint is needed, it should be defined outside the auth middleware group
        // and UserProfileController::getUserPublicKey might need adjustments or a separate method.
        // For now, let's assume only authenticated users can fetch other users' public keys.
});
