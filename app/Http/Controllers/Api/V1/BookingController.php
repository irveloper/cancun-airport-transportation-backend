<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validateBookingRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            
            $customer = Customer::findOrCreateFromBookingData($data['customerInfo']);

            $serviceType = ServiceType::where('code', $data['serviceName'])
                                     ->orWhere('name', $data['serviceName'])
                                     ->first();

            if (!$serviceType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid service type'
                ], 422);
            }

            $rateValidation = $this->validateRates($data);
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

            if (isset($data['serviceId'])) {
                $vehicleType = \App\Models\VehicleType::find($data['serviceId']);
                if ($vehicleType) {
                    $bookingData['vehicle_type_id'] = $vehicleType->id;
                }
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

    public function show($id): JsonResponse
    {
        $booking = Booking::with(['customer', 'serviceType', 'vehicleType', 'fromLocation', 'toLocation'])
                         ->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking->toApiResponse()
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,in_progress,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|string|max:500',
            'special_requests' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
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

    public function destroy($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
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

    private function validateBookingRequest(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'customerInfo' => 'required|array',
            'customerInfo.firstName' => 'required|string|max:100',
            'customerInfo.lastName' => 'nullable|string|max:100',
            'customerInfo.email' => 'required|email|max:255',
            'customerInfo.phone' => 'required|string|max:20',
            'customerInfo.country' => 'required|string|max:100',
            
            'pickupLocation' => 'required|string|max:255',
            'dropoffLocation' => 'required|string|max:255',
            'pickupDateTime' => 'required|date|after:now',
            'passengers' => 'required|integer|min:1|max:50',
            'serviceId' => 'nullable|exists:vehicle_types,id',
            'serviceName' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
            'totalPrice' => 'required|numeric|min:0',
            'specialRequests' => 'nullable|string|max:1000',
            
            'childSeats' => 'sometimes|integer|min:0|max:10',
            'wheelchairAccessible' => 'sometimes|boolean',
            'hotelReservationName' => 'nullable|string|max:255',
            'fromLocationId' => 'required|exists:locations,id',
            'toLocationId' => 'required|exists:locations,id',
            'fromLocationType' => 'required|in:airport,location,zone',
            'toLocationType' => 'required|in:airport,location,zone',
            'tripType' => 'required|in:arrival,departure,round-trip,hotel-to-hotel',
            'bookingDate' => 'required|date',
            
            'arrivalFlightInfo' => 'nullable|array',
            'arrivalFlightInfo.airline' => 'required_with:arrivalFlightInfo|string|max:100',
            'arrivalFlightInfo.flightNumber' => 'required_with:arrivalFlightInfo|string|max:20',
            'arrivalFlightInfo.date' => 'required_with:arrivalFlightInfo|date',
            'arrivalFlightInfo.time' => 'required_with:arrivalFlightInfo|date_format:H:i',
            
            'departureFlightInfo' => 'nullable|array',
            'departureFlightInfo.airline' => 'required_with:departureFlightInfo|string|max:100',
            'departureFlightInfo.flightNumber' => 'required_with:departureFlightInfo|string|max:20',
            'departureFlightInfo.date' => 'required_with:departureFlightInfo|date',
            'departureFlightInfo.time' => 'required_with:departureFlightInfo|date_format:H:i',
        ]);
    }

    private function validateRates(array $data): array
    {
        try {
            $serviceType = ServiceType::where('code', $data['serviceName'])
                                     ->orWhere('name', $data['serviceName'])
                                     ->first();

            if (!$serviceType) {
                return ['valid' => false, 'message' => 'Service type not found'];
            }

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

            return ['valid' => true, 'rate' => $validRate];

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error validating rates: ' . $e->getMessage()];
        }
    }
}
