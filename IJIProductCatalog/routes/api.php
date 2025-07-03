<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\BrandController as AdminBrandController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\MasterProductController as AdminMasterProductController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\ProductProposalController as AdminProductProposalController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\ProductAttributeController as AdminProductAttributeController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Admin\MasterProductVariationController as AdminMasterProductVariationController;
use IJIDeals\IJIProductCatalog\Http\Controllers\Shop\ProductProposalController as ShopProductProposalController;

Route::group(config('ijiproductcatalog.routes.middleware'), function () {
    // Admin Routes
    Route::prefix(config('ijiproductcatalog.routes.prefix') . '/admin')
        ->middleware(config('ijiproductcatalog.routes.admin_middleware'))
        ->group(function () {
            Route::apiResource('brands', AdminBrandController::class)->except(['create', 'edit']);
            Route::apiResource('categories', AdminCategoryController::class)->except(['create', 'edit']);

            Route::get('product-proposals', [AdminProductProposalController::class, 'index'])->name('product-proposals.index.admin');
            Route::get('product-proposals/{productProposal}', [AdminProductProposalController::class, 'show'])->name('product-proposals.show.admin');
            Route::post('product-proposals/{productProposal}/approve', [AdminProductProposalController::class, 'approve'])->name('product-proposals.approve');
            Route::post('product-proposals/{productProposal}/reject', [AdminProductProposalController::class, 'reject'])->name('product-proposals.reject');
            Route::post('product-proposals/{productProposal}/needs-revision', [AdminProductProposalController::class, 'needsRevision'])->name('product-proposals.needs-revision');

            Route::apiResource('master-products', AdminMasterProductController::class)->except(['create', 'edit']);

            Route::apiResource('product-attributes', AdminProductAttributeController::class)->except(['create', 'edit']);
            Route::post('product-attributes/{productAttribute}/values', [AdminProductAttributeController::class, 'storeValue'])->name('product-attributes.values.store');
            Route::put('product-attributes/{productAttribute}/values/{value}', [AdminProductAttributeController::class, 'updateValue'])->name('product-attributes.values.update');
            Route::delete('product-attributes/{productAttribute}/values/{value}', [AdminProductAttributeController::class, 'destroyValue'])->name('product-attributes.values.destroy');

            Route::apiResource('master-products.variations', AdminMasterProductVariationController::class)
                ->except(['create', 'edit'])
                ->shallow();
        });

    // Shop Routes (for Product Proposals)
    Route::prefix(config('ijiproductcatalog.routes.prefix') . '/shop')
        ->middleware(config('ijiproductcatalog.routes.shop_middleware'))
        ->group(function () {
            Route::get('product-proposals', [ShopProductProposalController::class, 'index'])->name('product-proposals.index');
            Route::post('product-proposals', [ShopProductProposalController::class, 'store'])->name('product-proposals.store');
            Route::get('product-proposals/{productProposal}', [ShopProductProposalController::class, 'show'])->name('product-proposals.show');
        });
});