<?php

use App\Http\Controllers\Api\V1\LookupController;
use Illuminate\Support\Facades\Route;

/*
 * The partner API. Integrations call it with an API key as a bearer token; see /docs/api.
 */
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'throttle:partner-api'])->group(function () {
    Route::middleware('capability:places:read')->group(function () {
        Route::get('categories', [LookupController::class, 'categories'])->name('categories.index');
        Route::get('tags', [LookupController::class, 'tags'])->name('tags.index');
        Route::get('states', [LookupController::class, 'states'])->name('states.index');
    });
});
