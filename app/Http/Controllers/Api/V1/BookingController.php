<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\CurrencyExchange;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['customer', 'serviceType', 'fromLocation', 'toLocation']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('trip_type')) {
            $query->where('trip_type', $request->trip_type);
        }

        if ($request->has('customer_email')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('email', $request->customer_email);
            });
        }

        if ($request->has('date_from')) {
            $query->where('pickup_date_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('pickup_date_time', '<=', $request->date_to);
        }

        $bookings = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($booking) => $booking->toApiResponse()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'last_page' => $bookings->lastPage(),
            ]
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            $customer = Customer::findOrCreateFromBookingData($data['customerInfo']);

            // Resolve service and vehicle types
            $typeResolution = $this->resolveServiceAndVehicleTypes($data);
            if (!$typeResolution['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $typeResolution['message']
                ], 422);
            }

            $serviceType = $typeResolution['serviceType'];
            $vehicleType = $typeResolution['vehicleType'];

            $rateValidation = $this->validateRates($data, $serviceType);
            if (!$rateValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $rateValidation['message']
                ], 422);
            }

            $exchangeRate = CurrencyExchange::getExchangeRate('USD', $data['currency']);

            $bookingData = [
                'booking_number' => Booking::generateBookingNumber(),
                'customer_id' => $customer->id,
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $vehicleType->id,
                'service_name' => $data['serviceName'],
                'from_location_id' => $data['fromLocationId'],
                'to_location_id' => $data['toLocationId'],
                'pickup_location' => $data['pickupLocation'],
                'dropoff_location' => $data['dropoffLocation'],
                'from_location_type' => $data['fromLocationType'],
                'to_location_type' => $data['toLocationType'],
                'trip_type' => $data['tripType'],
                'pickup_date_time' => Carbon::parse($data['pickupDateTime']),
                'passengers' => $data['passengers'],
                'child_seats' => $data['childSeats'] ?? 0,
                'wheelchair_accessible' => $data['wheelchairAccessible'] ?? false,
                'currency' => $data['currency'],
                'total_price' => $data['totalPrice'],
                'exchange_rate' => $exchangeRate,
                'special_requests' => $data['specialRequests'] ?? null,
                'hotel_reservation_name' => $data['hotelReservationName'] ?? null,
                'booking_date' => Carbon::parse($data['bookingDate']),
                'status' => 'pending',
            ];

            if (isset($data['arrivalFlightInfo'])) {
                $bookingData['arrival_flight_info'] = $data['arrivalFlightInfo'];
            }

            if (isset($data['departureFlightInfo'])) {
                $bookingData['departure_flight_info'] = $data['departureFlightInfo'];
            }

            $booking = Booking::create($bookingData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => $booking->toApiResponse()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show($identifier): JsonResponse
    {
        $booking = $this->findBookingByIdentifier($identifier);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found with ID or booking number: ' . $identifier
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking->toApiResponse()
        ]);
    }

    public function update(UpdateBookingRequest $request, $identifier): JsonResponse
    {
        $booking = $this->findBookingByIdentifier($identifier, false); // No relations needed for update

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found with ID or booking number: ' . $identifier
            ], 404);
        }

        try {
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'confirmed':
                        $booking->confirm();
                        break;
                    case 'cancelled':
                        $booking->cancel($request->cancellation_reason);
                        break;
                    case 'in_progress':
                        $booking->markInProgress();
                        break;
                    case 'completed':
                        $booking->markCompleted();
                        break;
                }
            }

            if ($request->has('special_requests')) {
                $booking->update(['special_requests' => $request->special_requests]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully',
                'data' => $booking->fresh()->toApiResponse()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy($identifier): JsonResponse
    {
        $booking = $this->findBookingByIdentifier($identifier, false); // No relations needed for destroy

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found with ID or booking number: ' . $identifier
            ], 404);
        }

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be cancelled'
            ], 422);
        }

        try {
            $booking->cancel('Cancelled by customer');

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Find booking by ID or booking number
     */
    private function findBookingByIdentifier($identifier, $withRelations = true)
    {
        $query = $withRelations 
            ? Booking::with(['customer', 'serviceType', 'vehicleType', 'fromLocation', 'toLocation'])
            : Booking::query();
        
        $booking = null;
        
        if (is_numeric($identifier)) {
            // Search by ID first
            $booking = $query->find($identifier);
        }
        
        // If not found by ID or if identifier is not numeric, try booking_number
        if (!$booking) {
            $booking = $query->where('booking_number', $identifier)->first();
        }
        
        return $booking;
    }


    /**
     * Map frontend service names to our database service types
     * Note: This is now used for backward compatibility only
     * New logic properly separates serviceName (vehicle) from tripType (service)
     */
    private function mapServiceName(string $serviceName): string
    {
        $serviceMappings = [
            'standard private' => 'One Way',
            'private' => 'One Way',
            'standard' => 'One Way',
            'round-trip' => 'Round Trip',
            'roundtrip' => 'Round Trip',
            'hotel-to-hotel' => 'Hotel to Hotel',
            'hotel to hotel' => 'Hotel to Hotel',
            'arrival' => 'One Way',
            'departure' => 'One Way',
        ];

        $lowerServiceName = strtolower($serviceName);
        $mapped = $serviceMappings[$lowerServiceName] ?? $serviceName;

        return $mapped;
    }

    /**
     * Helper method to resolve service and vehicle types from request data
     */
    private function resolveServiceAndVehicleTypes(array $data): array
    {
        // serviceName contains the vehicle type (CRAFTER, standard private, etc.)
        $vehicleType = \App\Models\VehicleType::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($data['serviceName']) . '%'])
                                              ->orWhere('code', $data['serviceName'])
                                              ->first();

        if (!$vehicleType) {
            return [
                'success' => false,
                'message' => 'Invalid vehicle type: ' . $data['serviceName']
            ];
        }

        // tripType contains the service type (arrival, round-trip, etc.)
        $serviceTypeMapping = [
            'arrival' => 'One Way',
            'departure' => 'One Way', 
            'round-trip' => 'Round Trip',
            'hotel-to-hotel' => 'Hotel to Hotel'
        ];

        $serviceTypeName = $serviceTypeMapping[$data['tripType']] ?? null;
        if (!$serviceTypeName) {
            return [
                'success' => false,
                'message' => 'Invalid trip type: ' . $data['tripType']
            ];
        }

        $serviceType = ServiceType::where('name', $serviceTypeName)->first();
        if (!$serviceType) {
            return [
                'success' => false,
                'message' => 'Service type not found: ' . $serviceTypeName . ' (available: ' . ServiceType::pluck('name')->implode(', ') . ')'
            ];
        }

        return [
            'success' => true,
            'serviceType' => $serviceType,
            'vehicleType' => $vehicleType
        ];
    }

    /**
     * Normalize flight time formats
     */
    private function normalizeFlightTime(string $time): string
    {
        // If time is in "12:12" format, that's fine
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time;
        }

        // Try to parse other formats
        try {
            $parsedTime = Carbon::createFromFormat('H:i', $time);
            return $parsedTime->format('H:i');
        } catch (\Exception $e) {
            // Return as is, validation will handle it
            return $time;
        }
    }


    private function validateRates(array $data, $serviceType = null): array
    {
        try {
            // If service type is already resolved, use it
            if (!$serviceType) {
                $typeResolution = $this->resolveServiceAndVehicleTypes($data);
                if (!$typeResolution['success']) {
                    return ['valid' => false, 'message' => $typeResolution['message']];
                }
                $serviceType = $typeResolution['serviceType'];
                $vehicleType = $typeResolution['vehicleType'];
            }

            Log::info('Validating rates for service', [
                'serviceName' => $data['serviceName'] ?? 'NOT SET',
                'tripType' => $data['tripType'] ?? 'NOT SET', 
                'resolvedServiceType' => $serviceType->name ?? 'NOT SET'
            ]);

            $rates = Rate::findForRoute(
                $serviceType->id,
                $data['fromLocationId'],
                $data['toLocationId'],
                Carbon::parse($data['pickupDateTime'])->format('Y-m-d')
            );

            if ($rates->isEmpty()) {
                return ['valid' => false, 'message' => 'No rates available for this route and date'];
            }

            $validRate = $rates->filter(function ($rate) use ($data) {
                if ($data['tripType'] === 'round-trip') {
                    return $rate->total_round_trip && $rate->total_round_trip <= ($data['totalPrice'] + 5);
                } else {
                    return $rate->total_one_way && $rate->total_one_way <= ($data['totalPrice'] + 5);
                }
            })->first();

            if (!$validRate) {
                return ['valid' => false, 'message' => 'Price does not match available rates'];
            }

            return [
                'valid' => true,
                'service_type' => $serviceType,
                'vehicle_type' => $validRate->vehicleType ?? null,
                'rate' => $validRate,
                'exchange_rate' => 1.0 // Default exchange rate
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error validating rates: ' . $e->getMessage()];
        }
    }

    /**
     * Debug endpoint to test service mapping and validation
     */
    public function debugServiceMapping(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->all();

        // Show original data
        $debug = [
            'original_data' => $data,
            'service_types_in_db' => ServiceType::all()->map(function ($st) {
                return ['id' => $st->id, 'name' => $st->name, 'code' => $st->code];
            }),
        ];

        try {
            // Test service name mapping specifically
            if (isset($data['serviceName'])) {
                $debug['service_mapping'] = [
                    'original' => $data['serviceName'],
                    'found_in_db' => null
                ];

                // Test database lookup using the service name
                $serviceType = ServiceType::where('code', $data['serviceName'])
                                         ->orWhere('name', $data['serviceName'])
                                         ->first();

                if ($serviceType) {
                    $debug['service_mapping']['found_in_db'] = [
                        'id' => $serviceType->id,
                        'name' => $serviceType->name,
                        'code' => $serviceType->code
                    ];
                }
            }

            // Test full validation (Form Request already validated the data)
            $debug['validation'] = [
                'passes' => true,
                'errors' => null
            ];

            $validatedData = $request->validated();

            // Test rate validation
            $rateValidation = $this->validateRates($validatedData);
            $debug['rate_validation'] = $rateValidation;

        } catch (\Exception $e) {
            $debug['error'] = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }

        return response()->json([
            'success' => true,
            'debug' => $debug
        ]);
    }

    /**
     * Create a new booking with payment intent in one call
     */
    public function createWithPayment(StoreBookingRequest $request): JsonResponse
    {
        try {

            // Validate additional payment-specific fields
            $paymentValidator = Validator::make($request->validated(), [
                'currency' => 'required|string|size:3|in:USD,EUR,GBP,MXN,CAD',
            ]);

            if ($paymentValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment validation failed',
                    'errors' => $paymentValidator->errors()
                ], 422);
            }

            $data = $request->validated();
            $currency = $paymentValidator->validated()['currency'];

            // Resolve service and vehicle types
            $typeResolution = $this->resolveServiceAndVehicleTypes($data);
            if (!$typeResolution['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $typeResolution['message']
                ], 422);
            }

            $serviceType = $typeResolution['serviceType'];
            $vehicleType = $typeResolution['vehicleType'];

            // Validate rates (reuse existing method)
            $rateValidation = $this->validateRates($data, $serviceType);
            if (!$rateValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $rateValidation['message']
                ], 422);
            }

            // Create or find customer
            $customer = Customer::findOrCreateFromBookingData($data['customerInfo']);

            // Generate unique booking number
            $bookingNumber = Booking::generateBookingNumber();

            // Create the booking
            $booking = Booking::create([
                'booking_number' => $bookingNumber,
                'customer_id' => $customer->id,
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $vehicleType->id,
                'service_name' => $data['serviceName'],
                'from_location_id' => $data['fromLocationId'],
                'to_location_id' => $data['toLocationId'],
                'pickup_location' => $data['pickupLocation'],
                'dropoff_location' => $data['dropoffLocation'],
                'from_location_type' => $data['fromLocationType'],
                'to_location_type' => $data['toLocationType'],
                'trip_type' => $data['tripType'],
                'pickup_date_time' => $data['pickupDateTime'],
                'passengers' => $data['passengers'],
                'child_seats' => $data['childSeats'] ?? 0,
                'wheelchair_accessible' => $data['wheelchairAccessible'] ?? false,
                'currency' => $currency,
                'total_price' => $data['totalPrice'],
                'exchange_rate' => 1.0, // Default exchange rate for now
                'arrival_flight_info' => $data['arrivalFlightInfo'] ?? null,
                'departure_flight_info' => $data['departureFlightInfo'] ?? null,
                'special_requests' => $data['specialRequests'] ?? null,
                'hotel_reservation_name' => $data['hotelReservationName'] ?? null,
                'booking_date' => $data['bookingDate'],
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);

            // Now create the Stripe payment intent
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                // Convert amount to cents for Stripe
                $amount = (int)($booking->total_price * 100);
                $stripeCurrency = strtolower($currency);

                // Create payment intent
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => $stripeCurrency,
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'booking_id' => $booking->id,
                        'booking_number' => $booking->booking_number,
                        'customer_email' => $customer->email,
                        'service_type' => $booking->service_name,
                        'pickup_location' => $booking->pickup_location,
                        'dropoff_location' => $booking->dropoff_location,
                    ],
                    'description' => "FiveStars Transfer - {$booking->booking_number}",
                    'receipt_email' => $customer->email,
                ]);

                // Update booking with payment intent ID
                $booking->update([
                    'stripe_payment_intent_id' => $paymentIntent->id
                ]);

                Log::info('Booking with payment intent created', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $amount,
                    'currency' => $stripeCurrency
                ]);

                // Return booking with payment details
                return response()->json([
                    'success' => true,
                    'message' => 'Booking created with payment intent successfully',
                    'data' => [
                        'booking' => $booking->toApiResponse(),
                        'payment' => [
                            'client_secret' => $paymentIntent->client_secret,
                            'payment_intent_id' => $paymentIntent->id,
                            'amount' => $amount,
                            'currency' => $stripeCurrency,
                            'expires_at' => now()->addHour()->toISOString() // Payment intents expire after 1 hour
                        ]
                    ]
                ], 201);

            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Stripe error during booking creation', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'stripe_code' => $e->getStripeCode()
                ]);

                // Booking was created but payment intent failed
                $booking->update(['payment_status' => 'failed', 'payment_failure_reason' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Booking created but payment processing failed: ' . $e->getMessage()
                ], 500);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating booking with payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking with payment'
            ], 500);
        }
    }
}
