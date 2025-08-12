<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ZoneController;
use App\Http\Controllers\Api\LocationController;

Route::apiResource('cities', CityController::class);
Route::apiResource('zones', ZoneController::class);
Route::apiResource('locations', LocationController::class);
