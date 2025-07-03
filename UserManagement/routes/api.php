<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\UserManagement\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your package. These
| routes are loaded by the UserManagementServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1/user-management', 'as' => 'api.user-management.'], function () {
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{id}', [UserController::class, 'update'])->name('users.update');
    // Placeholder for future API routes
    // Route::post('users', [UserController::class, 'store'])->name('users.store');
    // Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
});
