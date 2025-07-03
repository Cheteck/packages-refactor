<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\IJICommerce\Http\Controllers\ShopController;
use IJIDeals\IJICommerce\Http\Controllers\ShopTeamController;
use IJIDeals\IJICommerce\Http\Controllers\ProductProposalController;
use IJIDeals\IJICommerce\Http\Controllers\ShopProductController;
use IJIDeals\IJICommerce\Http\Controllers\ShopProductVariationController;
use IJIDeals\IJICommerce\Http\Controllers\OrderController as CustomerOrderController; // Customer facing
use IJIDeals\IJICommerce\Http\Controllers\Shop\OrderController as ShopOrderController; // Shop facing
use IJIDeals\IJICommerce\Http\Controllers\Admin\BrandController as AdminBrandController;
use IJIDeals\IJICommerce\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use IJIDeals\IJICommerce\Http\Controllers\Admin\MasterProductController as AdminMasterProductController;
use IJIDeals\IJICommerce\Http\Controllers\Admin\ProductProposalController as AdminProductProposalController;
use IJIDeals\IJICommerce\Http\Controllers\Admin\ProductAttributeController as AdminProductAttributeController;
use IJIDeals\IJICommerce\Http\Controllers\Admin\MasterProductVariationController as AdminMasterProductVariationController;

/*
|--------------------------------------------------------------------------
| IJICommerce API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Shops
    Route::post('shops', [ShopController::class, 'store'])->name('shops.store');
    Route::get('shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
    Route::put('shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
    Route::delete('shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');
    Route::get('shops', [ShopController::class, 'index'])->name('shops.index');

    // Shop Team Management
    Route::group(['prefix' => 'shops/{shop}/team', 'as' => 'shops.team.'], function () {
        Route::get('/', [ShopTeamController::class, 'index'])->name('index');
        Route::post('/users', [ShopTeamController::class, 'addUser'])->name('users.add');
        Route::put('/users/{user_id}', [ShopTeamController::class, 'updateUserRole'])->name('users.update_role');
        Route::delete('/users/{user_id}', [ShopTeamController::class, 'removeUser'])->name('users.remove');
    });

    

    

    

    // Platform Admin Routes
    Route::group(['prefix' => 'admin', 'as' => 'admin.', /* 'middleware' => ['auth:sanctum', 'platform.admin'] */], function() {
        
    });
});

// Publicly accessible shop routes (if any)
// Route::get('public/shops/{slug}', [ShopController::class, 'publicShow'])->name('public.shops.show');
