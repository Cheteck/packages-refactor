<?php

use IJIDeals\Social\Http\Controllers\CommentController;
use IJIDeals\Social\Http\Controllers\FollowController;
use IJIDeals\Social\Http\Controllers\LikeController;
use IJIDeals\Social\Http\Controllers\NotificationController;
use IJIDeals\Social\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('v1/social')->name('social.')->group(function () {
    // Posts
    // Replaced Route::apiResource('posts', PostController::class); to selectively apply middleware
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store')->middleware('throttle:10,1');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::patch('posts/{post}', [PostController::class, 'update'])->name('posts.update.patch');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Comments (nested under posts)
    // Route::apiResource('posts.comments', CommentController::class)->shallow()->except(['show']);
    // Correcting to ensure index, store are post-specific, others are top-level.
    Route::get('posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments.index');
    Route::post('posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store')->middleware('throttle:50,1');
    Route::get('comments/{comment}', [CommentController::class, 'show'])->name('comments.show');
    Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::patch('comments/{comment}', [CommentController::class, 'update'])->name('comments.update.patch'); // often good to have patch too
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Likes (nested under posts for context, actions are specific)
    Route::get('posts/{post}/likes', [LikeController::class, 'index'])->name('posts.likes.index');
    Route::post('posts/{post}/likes', [LikeController::class, 'store'])->name('posts.likes.store')->middleware('throttle:100,1');
    // For unliking, the user's like is specific to the post, but the like ID itself isn't usually passed.
    // The controller's destroy method is set up to find the like by user_id and post_id.
    Route::delete('posts/{post}/likes', [LikeController::class, 'destroy'])->name('posts.likes.destroy')->middleware('throttle:100,1');

    // Follows (User specific)
    Route::get('users/{user}/followers', [FollowController::class, 'followers'])->name('users.followers');
    Route::get('users/{user}/following', [FollowController::class, 'following'])->name('users.following');
    Route::post('users/{userToFollow}/follow', [FollowController::class, 'store'])->name('users.follow')->middleware('throttle:50,1');
    Route::delete('users/{userToUnfollow}/unfollow', [FollowController::class, 'destroy'])->name('users.unfollow')->middleware('throttle:50,1');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
