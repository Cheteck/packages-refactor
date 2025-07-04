<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auction System API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your auction system package.
| These routes are loaded by the AuctionSystemServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use IJIDeals\AuctionSystem\Http\Controllers\Api\AuctionController;

// Auction routes
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');

// Bid routes
use IJIDeals\AuctionSystem\Http\Controllers\Api\BidController;

Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])
    ->name('auctions.bids.store')
    ->middleware(config('auction-system.auth_middleware', 'auth:sanctum')); // Apply auth middleware
