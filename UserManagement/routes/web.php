<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\UserManagement\Http\Controllers\UserController;

// It's good practice to name route groups for packages to avoid conflicts
Route::group(['prefix' => 'user-management', 'as' => 'user-management.', 'middleware' => ['web']], function () {
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{id}', [UserController::class, 'update'])->name('users.update');
    // Add other routes as needed, e.g., for user creation, deletion, listing.
});

// You might also want API routes, create a routes/api.php file and load it in the Service Provider
// Example for api.php:
/*
Route::group(['prefix' => 'api/user-management', 'middleware' => ['api']], function () {
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
});
*/
