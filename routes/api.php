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
use App\Http\Controllers\Api\V1\HighlightedQuoteController;
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
    Route::get('cities/{city}/rates', [CityController::class, 'getCityRates']);

    // Zones API Routes
    Route::apiResource('zones', ZoneController::class);
    Route::get('cities/{city}/zones', [ZoneController::class, 'byCity']);

    // Locations API Routes
    Route::apiResource('locations', LocationController::class);
    Route::get('cities/{city}/locations', [LocationController::class, 'byCity']);
    Route::get('locations/type/{type}', [LocationController::class, 'byType']);

    // Quote API Route
    Route::get('/quote', [QuoteController::class, 'getQuote']);

    // Highlighted/Featured Quotes API Routes
    Route::get('/highlighted-quotes', [HighlightedQuoteController::class, 'index']);
    Route::get('/highlighted-quotes/{id}', [HighlightedQuoteController::class, 'show']);

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
    Route::post('/contact/group-quote', [ContactController::class, 'storeGroupQuote']);
    Route::get('/contacts', [ContactController::class, 'index']); // Admin only
    Route::get('/contacts/stats', [ContactController::class, 'stats']); // Admin only
    Route::get('/contacts/{id}', [ContactController::class, 'show']); // Admin only
    Route::patch('/contacts/{id}/status', [ContactController::class, 'updateStatus']); // Admin only
    
    // Debug route for testing service name mapping
    Route::post('/debug/service-mapping', [BookingController::class, 'debugServiceMapping']);
    
    // Debug route for previewing email templates (no actual sending)
    Route::post('/debug/preview-email', function (Request $request) {
        $request->validate([
            'type' => 'required|in:booking,contact',
            'booking_id' => 'nullable|exists:bookings,id',
            'format' => 'sometimes|in:html,text,both'
        ]);
        
        $format = $request->get('format', 'html');
        
        try {
            if ($request->type === 'booking') {
                if ($request->booking_id) {
                    $booking = \App\Models\Booking::with(['customer', 'serviceType', 'vehicleType'])->find($request->booking_id);
                } else {
                    // Create temporary booking for preview
                    $customer = new \App\Models\Customer([
                        'first_name' => 'Preview',
                        'last_name' => 'Customer',
                        'email' => 'preview@example.com',
                        'phone' => '+1-555-PREVIEW',
                        'country' => 'Preview Country'
                    ]);
                    
                    $booking = new \App\Models\Booking([
                        'booking_number' => 'PREVIEW-' . now()->format('YmdHis'),
                        'service_name' => 'Preview Transfer Service',
                        'pickup_location' => 'Preview Pickup Location',
                        'dropoff_location' => 'Preview Dropoff Location',
                        'pickup_date_time' => now()->addDays(1),
                        'passengers' => 2,
                        'currency' => 'USD',
                        'total_price' => 150.00,
                        'special_requests' => 'This is a preview of the booking confirmation email.',
                        'status' => 'confirmed'
                    ]);
                    $booking->setRelation('customer', $customer);
                }
                
                $mailable = new \App\Mail\BookingConfirmation($booking);
                
            } else {
                $contact = new \App\Models\Contact([
                    'name' => 'Preview User',
                    'email' => 'preview@example.com',
                    'subject' => 'Email Preview Test',
                    'message' => 'This is a preview of the contact auto-reply email template.',
                    'status' => 'new'
                ]);
                
                $mailable = new \App\Mail\ContactAutoReply($contact);
            }
            
            $response = [
                'success' => true,
                'template_type' => $request->type,
                'format' => $format
            ];
            
            if ($format === 'html' || $format === 'both') {
                $response['html_preview'] = $mailable->render();
            }
            
            if ($format === 'text' || $format === 'both') {
                $response['text_preview'] = $mailable->textView ? view($mailable->textView, $mailable->buildViewData())->render() : 'No text template available';
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview email template: ' . $e->getMessage()
            ], 500);
        }
    });

    // Debug route for testing email functionality
    Route::post('/debug/test-email', function (Request $request) {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:booking,contact',
            'booking_id' => 'nullable|exists:bookings,id'
        ]);
        
        try {
            if ($request->type === 'booking') {
                if ($request->booking_id) {
                    $booking = \App\Models\Booking::with(['customer', 'serviceType', 'vehicleType'])->find($request->booking_id);
                } else {
                    // Create test booking
                    $customer = \App\Models\Customer::firstOrCreate(
                        ['email' => $request->email],
                        ['first_name' => 'Test', 'last_name' => 'User', 'phone' => '+1-555-TEST', 'country' => 'Test']
                    );
                    
                    $booking = \App\Models\Booking::create([
                        'booking_number' => 'TEST-' . now()->format('YmdHis'),
                        'customer_id' => $customer->id,
                        'service_type_id' => 1,
                        'vehicle_type_id' => 1,
                        'service_name' => 'Test Service',
                        'from_location_id' => 1,
                        'to_location_id' => 2,
                        'pickup_location' => 'Test Pickup',
                        'dropoff_location' => 'Test Dropoff',
                        'from_location_type' => 'airport',
                        'to_location_type' => 'location',
                        'trip_type' => 'arrival',
                        'pickup_date_time' => now()->addDays(1),
                        'passengers' => 2,
                        'currency' => 'USD',
                        'total_price' => 100.00,
                        'exchange_rate' => 1.0,
                        'booking_date' => now(),
                        'status' => 'confirmed'
                    ]);
                }
                
                \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\BookingConfirmation($booking));
                $message = 'Booking confirmation email sent successfully';
                
            } else {
                $contact = new \App\Models\Contact([
                    'name' => 'Test User',
                    'email' => $request->email,
                    'subject' => 'Test Email',
                    'message' => 'Test message',
                    'status' => 'new'
                ]);
                
                \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\ContactAutoReply($contact));
                $message = 'Contact auto-reply email sent successfully';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'email_sent_to' => $request->email,
                'email_type' => $request->type
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    });

    // Email system status and diagnostics
    Route::get('/debug/email-status', function () {
        try {
            $status = [
                'success' => true,
                'timestamp' => now()->toISOString(),
                'system_status' => 'operational',
                'mail_configuration' => [
                    'driver' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'sendgrid_configured' => !empty(config('services.sendgrid.api_key')),
                    'queue_driver' => config('queue.default'),
                ],
                'email_templates' => [
                    'booking_confirmation' => [
                        'html' => view()->exists('emails.booking-confirmation'),
                        'text' => view()->exists('emails.booking-confirmation-text'),
                        'class' => class_exists(\App\Mail\BookingConfirmation::class)
                    ],
                    'contact_auto_reply' => [
                        'html' => view()->exists('emails.contact-auto-reply'),
                        'text' => view()->exists('emails.contact-auto-reply-text'),
                        'class' => class_exists(\App\Mail\ContactAutoReply::class)
                    ],
                    'contact_form_submitted' => [
                        'html' => view()->exists('emails.contact-form-submitted'),
                        'text' => view()->exists('emails.contact-form-submitted-text'),
                        'class' => class_exists(\App\Mail\ContactFormSubmitted::class)
                    ]
                ],
                'database_status' => [
                    'bookings_count' => \App\Models\Booking::count(),
                    'customers_count' => \App\Models\Customer::count(),
                    'contacts_count' => \App\Models\Contact::count(),
                    'recent_bookings' => \App\Models\Booking::latest()->take(3)->pluck('id', 'booking_number')
                ]
            ];

            // Check queue status if using database queue
            if (config('queue.default') === 'database') {
                $status['queue_status'] = [
                    'pending_jobs' => \Illuminate\Support\Facades\DB::table('jobs')->count(),
                    'failed_jobs' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count()
                ];
            }

            // Test basic email configuration
            try {
                $testMailable = new \App\Mail\BookingConfirmation(new \App\Models\Booking());
                $status['email_render_test'] = 'success';
            } catch (\Exception $e) {
                $status['email_render_test'] = 'failed: ' . $e->getMessage();
                $status['system_status'] = 'degraded';
            }

            return response()->json($status);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'system_status' => 'error',
                'message' => 'Failed to get email system status: ' . $e->getMessage()
            ], 500);
        }
    });

    // Bulk email testing
    Route::post('/debug/bulk-test-email', function (Request $request) {
        $request->validate([
            'emails' => 'required|array|max:10',
            'emails.*' => 'email',
            'type' => 'required|in:booking,contact',
            'booking_id' => 'nullable|exists:bookings,id'
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($request->emails as $email) {
            try {
                if ($request->type === 'booking') {
                    if ($request->booking_id) {
                        $booking = \App\Models\Booking::with(['customer', 'serviceType', 'vehicleType'])->find($request->booking_id);
                    } else {
                        $customer = \App\Models\Customer::firstOrCreate(
                            ['email' => $email],
                            ['first_name' => 'Bulk Test', 'last_name' => 'User', 'phone' => '+1-555-BULK', 'country' => 'Test']
                        );
                        
                        $booking = \App\Models\Booking::create([
                            'booking_number' => 'BULK-' . now()->format('YmdHis') . '-' . substr(md5($email), 0, 4),
                            'customer_id' => $customer->id,
                            'service_type_id' => 1,
                            'vehicle_type_id' => 1,
                            'service_name' => 'Bulk Test Service',
                            'from_location_id' => 1,
                            'to_location_id' => 2,
                            'pickup_location' => 'Bulk Test Pickup',
                            'dropoff_location' => 'Bulk Test Dropoff',
                            'from_location_type' => 'airport',
                            'to_location_type' => 'location',
                            'trip_type' => 'arrival',
                            'pickup_date_time' => now()->addDays(1),
                            'passengers' => 2,
                            'currency' => 'USD',
                            'total_price' => 100.00,
                            'exchange_rate' => 1.0,
                            'booking_date' => now(),
                            'status' => 'confirmed'
                        ]);
                    }
                    
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\BookingConfirmation($booking));
                    
                } else {
                    $contact = new \App\Models\Contact([
                        'name' => 'Bulk Test User',
                        'email' => $email,
                        'subject' => 'Bulk Email Test',
                        'message' => 'Bulk test message',
                        'status' => 'new'
                    ]);
                    
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\ContactAutoReply($contact));
                }

                $results[] = [
                    'email' => $email,
                    'status' => 'sent',
                    'message' => 'Email queued successfully'
                ];
                $successCount++;

            } catch (\Exception $e) {
                $results[] = [
                    'email' => $email,
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Bulk email test completed. {$successCount} successful, {$failureCount} failed.",
            'summary' => [
                'total_emails' => count($request->emails),
                'successful' => $successCount,
                'failed' => $failureCount,
                'success_rate' => round(($successCount / count($request->emails)) * 100, 1) . '%'
            ],
            'results' => $results,
            'email_type' => $request->type
        ]);
    });
    
});

// Stripe Webhook Route (outside of v1 prefix and rate limiting)
Route::post('/stripe/webhook', [PaymentController::class, 'webhook'])->name('stripe.webhook');
