<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
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
    public function getQuote(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type' => 'required|string', // round-trip, one-way, hotel-to-hotel
                'from_location_id' => 'required|integer|exists:locations,id',
                'to_location_id' => 'required|integer|exists:locations,id',
                'pax' => 'required|integer|min:1|max:50',
                'date' => 'nullable|date|after_or_equal:today',
            ]);

            // Buscar el tipo de servicio
            $serviceTypeCode = $this->mapServiceTypeCode($validated['service_type']);
            $serviceType = ServiceType::where('code', $serviceTypeCode)
                                    ->orWhere('name', 'like', '%' . $validated['service_type'] . '%')
                                    ->first();

            if (!$serviceType) {
                return $this->errorResponse('Service type not found', 404);
            }

            // Obtener las ubicaciones
            $fromLocation = Location::with(['zone.city'])->find($validated['from_location_id']);
            $toLocation = Location::with(['zone.city'])->find($validated['to_location_id']);

            if (!$fromLocation || !$toLocation) {
                return $this->errorResponse('Location not found', 404);
            }

            // Determinar el serviceTypeTPV basado en el tipo de ubicación
            $serviceTypeTPV = $this->determineServiceTypeTPV($fromLocation, $toLocation, $serviceType);

            // Buscar rates disponibles para esta ruta
            $rates = Rate::with(['vehicleType.serviceFeatures'])
                        ->where('service_type_id', $serviceType->id)
                        ->where('from_location_id', $validated['from_location_id'])
                        ->where('to_location_id', $validated['to_location_id'])
                        ->valid($validated['date'] ?? now())
                        ->get();

            if ($rates->isEmpty()) {
                return $this->errorResponse('No rates available for this route', 404);
            }

            // Filtrar por capacidad de pasajeros
            $availableRates = $rates->filter(function ($rate) use ($validated) {
                return $rate->vehicleType->max_pax >= $validated['pax'];
            });

            if ($availableRates->isEmpty()) {
                return $this->errorResponse('No vehicles available for the requested number of passengers', 404);
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
                    'features' => $features, // Nueva estructura de features
                    'mUnits' => $vehicleType->max_units,
                    'mPax' => $vehicleType->max_pax,
                    'timeFromAirport' => $vehicleType->travel_time,
                    'video' => $vehicleType->video_url,
                    'frame' => $vehicleType->frame,
                    'numVehicles' => $rate->num_vehicles,
                    'costVehicleOW' => number_format($rate->cost_vehicle_one_way, 2, '.', ''),
                    'totalOW' => (int) $rate->total_one_way,
                    'costVehicleRT' => number_format($rate->cost_vehicle_round_trip, 2, '.', ''),
                    'totalRT' => (int) $rate->total_round_trip,
                    'available' => $rate->available ? 1 : 0
                ];
            }

            return $this->successResponse($response, 'Quote retrieved successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to get quote', $e, $request->all());
            return $this->errorResponse('Failed to get quote', 500);
        }
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
            'hotel-to-hotel' => 'HTH',
            'hotel to hotel' => 'HTH',
            'hotel_to_hotel' => 'HTH',
        ];

        $normalizedType = strtolower(trim($serviceType));
        
        return $mapping[$normalizedType] ?? strtoupper($normalizedType);
    }
}