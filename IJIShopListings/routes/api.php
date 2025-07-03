<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\IJIShopListings\Http\Controllers\ShopProductController;
use IJIDeals\IJIShopListings\Http\Controllers\ShopProductVariationController;

Route::group(config('ijishoplistings.routes.middleware'), function () {
    // Shop Routes
    Route::prefix(config('ijishoplistings.routes.prefix') . '/shops/{shop}')
        ->middleware(config('ijishoplistings.routes.shop_middleware'))
        ->group(function () {
            // Shop Product Listings
            Route::group(['prefix' => 'shop-products', 'as' => 'shop-products.'], function() {
                Route::get('available-master', [ShopProductController::class, 'indexMasterProducts'])->name('available-master.index');
                Route::get('/', [ShopProductController::class, 'indexShopProducts'])->name('index');
                Route::post('/', [ShopProductController::class, 'store'])->name('store');
                Route::get('/{shopProduct}', [ShopProductController::class, 'show'])->name('show');
                Route::put('/{shopProduct}', [ShopProductController::class, 'update'])->name('update');
                Route::delete('/{shopProduct}', [ShopProductController::class, 'destroy'])->name('destroy');
                Route::post('/{shopProduct}/acknowledge-update', [ShopProductController::class, 'acknowledgeMasterProductUpdate'])->name('acknowledge-update');

                // Shop Product Variations (within a ShopProduct)
                Route::put('/{shopProduct}/variations/{variation}', [ShopProductVariationController::class, 'update'])->name('variations.update');
            });
        });
});