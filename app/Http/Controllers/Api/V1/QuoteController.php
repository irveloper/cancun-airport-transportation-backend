<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Rate;
use App\Models\Location;
use App\Models\Quote;
use App\Models\CurrencyExchange;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class QuoteController extends BaseApiController
{
    public function getQuote(QuoteRequest $request): JsonResponse
    {
        try {
            // Input is already validated by QuoteRequest
            $validated = $request->validated();

            // Get currency parameter (default to USD)
            $currency = strtoupper($request->get('currency', 'USD'));
            $validated['currency'] = $currency;

            // Validate currency
            if (!in_array($currency, ['USD', 'MXN'])) {
                return $this->errorResponse('Currency not supported. Only USD and MXN are allowed.', 400);
            }

            // Create cache key for this quote request
            $cacheKey = $this->generateQuoteCacheKey($validated);
            
            // Try to get cached quote first
            return $this->getCachedData($cacheKey, function () use ($validated, $request) {
                return $this->generateQuote($validated, $request);
            }, 900); // Cache for 15 minutes

        } catch (Exception $e) {
            $this->logError('Failed to get quote', $e, $request->all());
            return $this->resourceErrorResponse('quote', 'invalid_parameters', 500);
        }
    }

    /**
     * Generate quote with performance monitoring
     */
    private function generateQuote(array $validated, Request $request): JsonResponse
    {
        return $this->monitorQueryPerformance(function () use ($validated, $request) {
            // Buscar el tipo de servicio
            $serviceTypeCode = $this->mapServiceTypeCode($validated['service_type']);
            $serviceType = $this->getCachedData("service_type:{$serviceTypeCode}", function () use ($serviceTypeCode, $validated) {
                return ServiceType::where('code', $serviceTypeCode)
                                ->orWhere('name', 'like', '%' . $validated['service_type'] . '%')
                                ->first();
            }, 3600); // Cache for 1 hour

            if (!$serviceType) {
                return $this->resourceErrorResponse('quote', 'not_found', 404);
            }

            // Obtener las ubicaciones con sus zonas y ciudades
            $fromLocation = $this->getCachedData("location:{$validated['from_location_id']}", function () use ($validated) {
                return Location::with(['zone.city'])->find($validated['from_location_id']);
            }, 3600);

            $toLocation = $this->getCachedData("location:{$validated['to_location_id']}", function () use ($validated) {
                return Location::with(['zone.city'])->find($validated['to_location_id']);
            }, 3600);

            if (!$fromLocation || !$toLocation) {
                return $this->resourceErrorResponse('location', 'not_found', 404);
            }

            // Determinar el serviceTypeTPV basado en el tipo de ubicación
            $serviceTypeTPV = $this->determineServiceTypeTPV($fromLocation, $toLocation, $serviceType);

            // Buscar rates disponibles usando el nuevo sistema zone-based
            $rates = Rate::findForRoute(
                $serviceType->id,
                $validated['from_location_id'],
                $validated['to_location_id'],
                $validated['date'] ?? now()
            );

            if ($rates->isEmpty()) {
                return $this->resourceErrorResponse('quote', 'rate_not_available', 404);
            }

            // Filtrar por capacidad de pasajeros
            $availableRates = $rates->filter(function ($rate) use ($validated) {
                return $rate->vehicleType->max_pax >= $validated['pax'];
            });

            if ($availableRates->isEmpty()) {
                return $this->errorResponse(__('api.business.no_available_vehicles'), 404);
            }

            // Get exchange rates for response
            $exchangeRateToUSD = CurrencyExchange::getExchangeRate($validated['currency'], 'USD');
            $exchangeRateToMXN = CurrencyExchange::getExchangeRate($validated['currency'], 'MXN');

            // Construir la respuesta
            $response = [
                'exchangeDollar' => number_format($exchangeRateToUSD, 6),
                'exchangeMXN' => number_format($exchangeRateToMXN, 6),
                'currency' => strtolower($validated['currency']),
                'fromHotelId' => (string) $fromLocation->id,
                'toHotelId' => (string) $toLocation->id,
                'fromHotel' => strtoupper($fromLocation->name),
                'toHotel' => strtoupper($toLocation->name),
                'toDestination' => strtolower($toLocation->zone->city->name ?? ''),
                'toDestinationId' => $toLocation->zone->city->id ?? null,
                'fromDestination' => strtolower($fromLocation->zone->city->name ?? ''),
                'fromDestinationId' => $fromLocation->zone->city->id ?? null,
                'serviceTypeTPV' => $serviceTypeTPV,
                'prices' => []
            ];

            // Obtener el idioma de la petición (default: inglés)
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';

            // Construir array de precios
            foreach ($availableRates as $rate) {
                $vehicleType = $rate->vehicleType;
                
                // Construir array de features
                $features = $vehicleType->serviceFeatures->map(function ($feature) use ($locale) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->getName($locale),
                        'description' => $feature->getDescription($locale),
                        'icon' => $feature->icon,
                    ];
                })->toArray();
                
                // Convert prices from base USD to requested currency
                $exchangeRate = CurrencyExchange::getExchangeRate('USD', $validated['currency']);
                
                $costOneWay = (float) $rate->cost_vehicle_one_way * $exchangeRate;
                $totalOneWay = (float) $rate->total_one_way * $exchangeRate;
                $costRoundTrip = $rate->cost_vehicle_round_trip ? (float) $rate->cost_vehicle_round_trip * $exchangeRate : null;
                $totalRoundTrip = $rate->total_round_trip ? (float) $rate->total_round_trip * $exchangeRate : null;

                $response['prices'][] = [
                    'id' => $vehicleType->id,
                    'name' => $vehicleType->name,
                    'pic' => $vehicleType->image,
                    'type' => $vehicleType->code,
                    'features' => $features,
                    'mUnits' => $vehicleType->max_units,
                    'mPax' => $vehicleType->max_pax,
                    'timeFromAirport' => $vehicleType->travel_time,
                    'video' => $vehicleType->video_url,
                    'frame' => $vehicleType->frame,
                    'numVehicles' => $rate->num_vehicles,
                    'costVehicleOW' => number_format($costOneWay, 2, '.', ''),
                    'totalOW' => (int) round($totalOneWay),
                    'costVehicleRT' => $costRoundTrip ? number_format($costRoundTrip, 2, '.', '') : null,
                    'totalRT' => $totalRoundTrip ? (int) round($totalRoundTrip) : null,
                    'available' => $rate->available ? 1 : 0
                ];
            }

            return $this->resourceResponse('quote', 'calculated', $response);
        }, 'quote_generation');
    }

    /**
     * Generate cache key for quote requests
     */
    private function generateQuoteCacheKey(array $validated): string
    {
        $date = $validated['date'] ?? now()->format('Y-m-d');
        $currency = $validated['currency'] ?? 'USD';
        return "quote:{$validated['service_type']}:{$validated['from_location_id']}:{$validated['to_location_id']}:{$validated['pax']}:{$date}:{$currency}";
    }

    /**
     * Determina el serviceTypeTPV basado en los tipos de ubicación
     */
    private function determineServiceTypeTPV(Location $fromLocation, Location $toLocation, ServiceType $serviceType): string
    {
        // Si cualquiera de las ubicaciones es un aeropuerto
        if ($fromLocation->type === 'A' || $toLocation->type === 'A') {
            return 'service_airport';
        }

        // Si ambas son hoteles u otras ubicaciones
        return 'service_hotel_hotel';
    }

    /**
     * Mapea los nombres de tipos de servicio a códigos
     */
    private function mapServiceTypeCode(string $serviceType): string
    {
        $mapping = [
            'round-trip' => 'RT',
            'round trip' => 'RT',
            'roundtrip' => 'RT',
            'one-way' => 'OW',
            'one way' => 'OW',
            'oneway' => 'OW',
            'arrival' => 'OW', // Airport arrival uses one-way rates
            'departure' => 'OW', // Airport departure uses one-way rates
            'hotel-to-hotel' => 'HTH',
            'hotel to hotel' => 'HTH',
            'hotel_to_hotel' => 'HTH',
        ];

        $normalizedType = strtolower(trim($serviceType));
        
        return $mapping[$normalizedType] ?? strtoupper($normalizedType);
    }

    /**
     * Save a quote for later reference
     */
    public function saveQuote(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type' => 'required|string|max:50|in:round-trip,one-way,hotel-to-hotel,arrival,departure',
                'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
                'from_location_id' => 'required|integer|exists:locations,id',
                'to_location_id' => 'required|integer|exists:locations,id|different:from_location_id',
                'pax' => 'required|integer|min:1|max:50',
                'service_date' => 'required|date|after_or_equal:today',
                'cost_vehicle_one_way' => 'required|numeric|min:0',
                'total_one_way' => 'required|numeric|min:0',
                'cost_vehicle_round_trip' => 'nullable|numeric|min:0',
                'total_round_trip' => 'nullable|numeric|min:0',
                'customer_email' => 'nullable|email',
                'customer_phone' => 'nullable|string|max:20',
                'customer_name' => 'nullable|string|max:100',
                'additional_data' => 'nullable|array',
            ]);

            // Get service type
            $serviceTypeCode = $this->mapServiceTypeCode($validated['service_type']);
            $serviceType = ServiceType::where('code', $serviceTypeCode)->first();
            
            if (!$serviceType) {
                return $this->resourceErrorResponse('service_type', 'not_found', 404);
            }

            // Create quote
            $quote = Quote::create([
                'quote_number' => Quote::generateQuoteNumber(),
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $validated['vehicle_type_id'],
                'from_location_id' => $validated['from_location_id'],
                'to_location_id' => $validated['to_location_id'],
                'pax' => $validated['pax'],
                'service_date' => $validated['service_date'],
                'cost_vehicle_one_way' => $validated['cost_vehicle_one_way'],
                'total_one_way' => $validated['total_one_way'],
                'cost_vehicle_round_trip' => $validated['cost_vehicle_round_trip'],
                'total_round_trip' => $validated['total_round_trip'],
                'status' => 'active',
                'expires_at' => now()->addHours(24),
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'additional_data' => $validated['additional_data'] ?? null,
            ]);

            return $this->resourceResponse('quote', 'created', $quote->toApiResponse());

        } catch (Exception $e) {
            $this->logError('Failed to save quote', $e, $request->all());
            return $this->resourceErrorResponse('quote', 'save_failed', 500);
        }
    }

    /**
     * Get a saved quote by quote number
     */
    public function getQuoteByNumber(Request $request, string $quoteNumber): JsonResponse
    {
        try {
            $quote = Quote::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation'])
                         ->where('quote_number', $quoteNumber)
                         ->first();

            if (!$quote) {
                return $this->resourceErrorResponse('quote', 'not_found', 404);
            }

            // Check if quote is expired
            if ($quote->is_expired) {
                return $this->resourceErrorResponse('quote', 'expired', 410);
            }

            return $this->resourceResponse('quote', 'retrieved', $quote->toApiResponse());

        } catch (Exception $e) {
            $this->logError('Failed to retrieve quote', $e, ['quote_number' => $quoteNumber]);
            return $this->resourceErrorResponse('quote', 'retrieval_failed', 500);
        }
    }

    /**
     * Get all quotes (with pagination and filters)
     */
    public function getAllQuotes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'nullable|string|in:draft,active,expired,booked',
                'service_type' => 'nullable|string',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $query = Quote::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation']);

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['service_type'])) {
                $serviceTypeCode = $this->mapServiceTypeCode($validated['service_type']);
                $serviceType = ServiceType::where('code', $serviceTypeCode)->first();
                if ($serviceType) {
                    $query->where('service_type_id', $serviceType->id);
                }
            }

            if (!empty($validated['from_date'])) {
                $query->whereDate('service_date', '>=', $validated['from_date']);
            }

            if (!empty($validated['to_date'])) {
                $query->whereDate('service_date', '<=', $validated['to_date']);
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate
            $perPage = $validated['per_page'] ?? 15;
            $quotes = $query->paginate($perPage);

            $response = [
                'quotes' => $quotes->items()->map(fn($quote) => $quote->toApiResponse()),
                'pagination' => [
                    'current_page' => $quotes->currentPage(),
                    'per_page' => $quotes->perPage(),
                    'total' => $quotes->total(),
                    'last_page' => $quotes->lastPage(),
                    'has_more_pages' => $quotes->hasMorePages(),
                ]
            ];

            return $this->resourceResponse('quotes', 'retrieved', $response);

        } catch (Exception $e) {
            $this->logError('Failed to retrieve quotes', $e, $request->all());
            return $this->resourceErrorResponse('quotes', 'retrieval_failed', 500);
        }
    }

    /**
     * Update quote status
     */
    public function updateQuoteStatus(Request $request, string $quoteNumber): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:draft,active,expired,booked',
            ]);

            $quote = Quote::where('quote_number', $quoteNumber)->first();

            if (!$quote) {
                return $this->resourceErrorResponse('quote', 'not_found', 404);
            }

            $quote->update(['status' => $validated['status']]);

            return $this->resourceResponse('quote', 'updated', $quote->toApiResponse());

        } catch (Exception $e) {
            $this->logError('Failed to update quote status', $e, [
                'quote_number' => $quoteNumber, 
                'request_data' => $request->all()
            ]);
            return $this->resourceErrorResponse('quote', 'update_failed', 500);
        }
    }

    /**
     * Delete a quote
     */
    public function deleteQuote(Request $request, string $quoteNumber): JsonResponse
    {
        try {
            $quote = Quote::where('quote_number', $quoteNumber)->first();

            if (!$quote) {
                return $this->resourceErrorResponse('quote', 'not_found', 404);
            }

            // Only allow deletion of draft or expired quotes
            if (!in_array($quote->status, ['draft', 'expired'])) {
                return $this->resourceErrorResponse('quote', 'cannot_delete_active_quote', 422);
            }

            $quote->delete();

            return $this->resourceResponse('quote', 'deleted', ['quote_number' => $quoteNumber]);

        } catch (Exception $e) {
            $this->logError('Failed to delete quote', $e, ['quote_number' => $quoteNumber]);
            return $this->resourceErrorResponse('quote', 'deletion_failed', 500);
        }
    }

    /**
     * Get quote statistics
     */
    public function getQuoteStats(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            $query = Quote::query();

            if (!empty($validated['from_date'])) {
                $query->whereDate('created_at', '>=', $validated['from_date']);
            }

            if (!empty($validated['to_date'])) {
                $query->whereDate('created_at', '<=', $validated['to_date']);
            }

            $stats = [
                'total_quotes' => $query->count(),
                'quotes_by_status' => $query->groupBy('status')
                                          ->selectRaw('status, count(*) as count')
                                          ->pluck('count', 'status')
                                          ->toArray(),
                'quotes_by_service_type' => $query->join('service_types', 'quotes.service_type_id', '=', 'service_types.id')
                                                 ->groupBy('service_types.name')
                                                 ->selectRaw('service_types.name, count(*) as count')
                                                 ->pluck('count', 'name')
                                                 ->toArray(),
                'average_quote_value' => [
                    'one_way' => $query->avg('total_one_way'),
                    'round_trip' => $query->whereNotNull('total_round_trip')->avg('total_round_trip'),
                ],
                'expired_quotes' => Quote::expired()->count(),
                'active_quotes' => Quote::active()->count(),
            ];

            return $this->resourceResponse('quote_stats', 'calculated', $stats);

        } catch (Exception $e) {
            $this->logError('Failed to calculate quote statistics', $e, $request->all());
            return $this->resourceErrorResponse('quote_stats', 'calculation_failed', 500);
        }
    }
}