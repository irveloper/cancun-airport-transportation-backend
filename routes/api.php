<?php

use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\AutocompleteController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\ServiceFeatureController;
use App\Http\Controllers\Api\V1\VehicleTypeController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Middleware\ApiRateLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API V1 Routes with Rate Limiting
Route::prefix('v1')->middleware([ApiRateLimit::class])->group(function () {

    // Health Check Endpoint
    Route::get('/health', function () {
        try {
            $cacheStatus = Cache::has('health_check');
            $databaseStatus = DB::connection()->getPdo();
            
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'timestamp' => now()->toISOString(),
                'version' => config('api.version', 'v1'),
                'services' => [
                    'cache' => $cacheStatus ? 'connected' : 'disconnected',
                    'database' => $databaseStatus ? 'connected' : 'disconnected',
                ],
                'uptime' => [
                    'started_at' => config('app.started_at', now()->toISOString()),
                    'uptime_seconds' => time() - strtotime(config('app.started_at', now()->toISOString())),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API health check failed',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage(),
            ], 503);
        }
    });

    // Autocomplete service for booking flow
    Route::get('/autocomplete/search', [AutocompleteController::class, 'search']);

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

    // Rates API Routes (specific routes first to avoid conflicts)
    Route::get('rates/route', [RateController::class, 'getRouteRates']);
    Route::get('rates/zone', [RateController::class, 'getZoneRates']);
    Route::apiResource('rates', RateController::class);

    // Booking API Routes (specific routes first to avoid conflicts)
    Route::post('/bookings/create-with-payment', [BookingController::class, 'createWithPayment']);
    Route::apiResource('bookings', BookingController::class);

    // Payment API Routes
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'getPaymentStatus']);
    Route::post('/bookings/{identifier}/payment-intent', [PaymentController::class, 'createPaymentIntentForBooking']);
    
    // Contact Form Routes
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/contacts', [ContactController::class, 'index']); // Admin only
    Route::get('/contacts/stats', [ContactController::class, 'stats']); // Admin only
    Route::get('/contacts/{id}', [ContactController::class, 'show']); // Admin only
    Route::patch('/contacts/{id}/status', [ContactController::class, 'updateStatus']); // Admin only
    
    // Debug route for testing service name mapping
    Route::post('/debug/service-mapping', [BookingController::class, 'debugServiceMapping']);
    
});

// Stripe Webhook Route (outside of v1 prefix and rate limiting)
Route::post('/stripe/webhook', [PaymentController::class, 'webhook'])->name('stripe.webhook');
