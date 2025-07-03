<?php

use IJIDeals\Subscriptions\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('subscriptions.api_routes.prefix', 'api/subscriptions'),
    'middleware' => config('subscriptions.api_routes.middleware', ['auth:sanctum']),
], function () {
    Route::get('plans', [SubscriptionController::class, 'indexPlans'])->name('subscriptions.plans.index');

    Route::get('current', [SubscriptionController::class, 'showUserSubscription'])->name('subscriptions.user.show');
    Route::post('subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.user.subscribe');
    Route::post('cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscriptions.user.cancel');

    // Future routes:
    // Route::post('switch-plan', [SubscriptionController::class, 'switchPlan'])->name('subscriptions.user.switch');
    // Route::post('resume', [SubscriptionController::class, 'resumeSubscription'])->name('subscriptions.user.resume');
    // Route::put('payment-method', [SubscriptionController::class, 'updatePaymentMethod'])->name('subscriptions.user.payment_method.update');
});
