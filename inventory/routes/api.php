<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your inventory package.
| These routes are loaded by the InventoryServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

use IJIDeals\Inventory\Http\Controllers\Api\InventoryLocationController;
use IJIDeals\Inventory\Http\Controllers\Api\InventoryController;
use IJIDeals\Inventory\Http\Controllers\Api\StockMovementController;

// Inventory Locations
Route::get('/locations', [InventoryLocationController::class, 'index'])->name('inventory.locations.index');
Route::get('/locations/{location}', [InventoryLocationController::class, 'show'])->name('inventory.locations.show');
// Add POST, PUT, DELETE for locations if full CRUD is needed:
// Route::post('/locations', [InventoryLocationController::class, 'store'])->name('inventory.locations.store');
// Route::put('/locations/{location}', [InventoryLocationController::class, 'update'])->name('inventory.locations.update');
// Route::delete('/locations/{location}', [InventoryLocationController::class, 'destroy'])->name('inventory.locations.destroy');


// Inventory (Stock Levels)
// The {productTypeAlias} will be mapped to a model class in the controller (e.g., 'masterproduct', 'shopproduct')
Route::get('/products/{productTypeAlias}/{productId}', [InventoryController::class, 'getForProduct'])->name('inventory.product.show');
Route::get('/locations/{location}/stock', [InventoryController::class, 'getForLocation'])->name('inventory.location.stock.index');


// Stock Movements
Route::get('/movements', [StockMovementController::class, 'index'])->name('inventory.movements.index');


// Optional: Inventory Adjustments (should be highly restricted)
// Route::post('/adjustments', [InventoryAdjustmentController::class, 'adjustStock'])->name('inventory.adjustments.store');
