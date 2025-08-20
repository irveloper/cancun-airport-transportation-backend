<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dash', function () {
        return Inertia::render('Dashboard');
    });
    
    // Dashboard CRUD routes
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Cities management
        Route::resource('cities', App\Http\Controllers\Dashboard\CityController::class);
        
        // Zones management  
        Route::resource('zones', App\Http\Controllers\Dashboard\ZoneController::class);
        
        // Locations management
        Route::resource('locations', App\Http\Controllers\Dashboard\LocationController::class);
        
        // Airports management
        Route::resource('airports', App\Http\Controllers\Dashboard\AirportController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
