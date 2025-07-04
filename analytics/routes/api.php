<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Analytics API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your analytics package.
| These routes are loaded by the AnalyticsServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

use IJIDeals\Analytics\Http\Controllers\Api\AnalyticsController;

// Route for platform summary statistics
Route::get('/summary', [AnalyticsController::class, 'getPlatformSummaryStats'])->name('analytics.summary');

// Route for specific trackable model statistics
// Using a placeholder {trackableTypeAlias} which needs to be resolved to a model type in the controller.
// The {trackableId} is the ID of the model instance.
Route::get('/trackable/{trackableTypeAlias}/{trackableId}', [AnalyticsController::class, 'getTrackableModelStats'])->name('analytics.trackable.stats');

// Example of how it might be called:
// /api/v1/analytics/trackable/product/123
// /api/v1/analytics/trackable/post/456
