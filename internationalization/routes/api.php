<?php

use IJIDeals\Internationalization\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('internationalization')->group(function () {
    Route::apiResource('languages', LanguageController::class);
    // TODO: Add translation management routes here
});
