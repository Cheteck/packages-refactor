<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\IJIOrderManagement\Http\Controllers\OrderController as CustomerOrderController;
use IJIDeals\IJIOrderManagement\Http\Controllers\Shop\OrderController as ShopOrderController;

Route::group(config('ijiordermanagement.routes.middleware'), function () {
    // Customer Routes
    Route::prefix(config('ijiordermanagement.routes.prefix'))
        ->middleware(config('ijiordermanagement.routes.customer_middleware'))
        ->group(function () {
            Route::get('orders', [CustomerOrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [CustomerOrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        });

    // Shop Routes
    Route::prefix(config('ijiordermanagement.routes.prefix') . '/shops/{shop}')
        ->middleware(config('ijiordermanagement.routes.shop_middleware'))
        ->group(function () {
            Route::get('orders', [ShopOrderController::class, 'index'])->name('shops.orders.index');
            Route::get('orders/{order}', [ShopOrderController::class, 'show'])->name('shops.orders.show');
            Route::put('orders/{order}/status', [ShopOrderController::class, 'updateStatus'])->name('shops.orders.updateStatus');
        });
});