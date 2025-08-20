<?php

use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\AutocompleteController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\ServiceFeatureController;
use App\Http\Controllers\Api\V1\VehicleTypeController;
use App\Http\Controllers\Api\V1\RateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API V1 Routes
Route::prefix('v1')->group(function () {

    // Autocomplete service for booking flow
    Route::get('/autocomplete', [AutocompleteController::class, 'search']);

    // Cities API Routes
    Route::apiResource('cities', CityController::class);
    Route::get('cities/{city}/details', [CityController::class, 'withDetails']);

    // Zones API Routes
    Route::apiResource('zones', ZoneController::class);
    Route::get('cities/{city}/zones', [ZoneController::class, 'byCity']);

    // Locations API Routes
    Route::apiResource('locations', LocationController::class);
    Route::get('cities/{city}/locations', [LocationController::class, 'byCity']);
    Route::get('locations/type/{type}', [LocationController::class, 'byType']);

    // Quote API Route
    Route::get('/quote', [QuoteController::class, 'getQuote']);

    // Service Features API Routes
    Route::apiResource('service-features', ServiceFeatureController::class);

    // Vehicle Types API Routes
    Route::apiResource('vehicle-types', VehicleTypeController::class);

    // Rates API Routes
    Route::apiResource('rates', RateController::class);

});
