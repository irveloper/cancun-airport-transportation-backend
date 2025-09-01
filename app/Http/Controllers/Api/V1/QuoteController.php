<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Rate;
use App\Models\Location;
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

            // Construir la respuesta
            $response = [
                'exchangeDollar' => '1.000000',
                'exchangeMXN' => '0.050251',
                'currency' => 'usd',
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
                    'costVehicleOW' => $rate->getFormattedPrice('one_way'),
                    'totalOW' => (int) $rate->total_one_way,
                    'costVehicleRT' => $rate->getFormattedPrice('round_trip'),
                    'totalRT' => (int) $rate->total_round_trip,
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
        return "quote:{$validated['service_type']}:{$validated['from_location_id']}:{$validated['to_location_id']}:{$validated['pax']}:{$date}";
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
}