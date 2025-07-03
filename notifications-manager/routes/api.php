<?php

use IJIDeals\NotificationsManager\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('notifications-manager.api_routes.prefix', 'api/notifications'),
    'middleware' => config('notifications-manager.api_routes.middleware', ['auth:sanctum']),
], function () {
    Route::get('preferences', [NotificationPreferenceController::class, 'index'])->name('notifications.preferences.index');
    Route::put('preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');
});
