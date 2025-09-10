<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Rate;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CityController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = City::query();

            // Apply search filter if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('state', 'ILIKE', "%{$search}%")
                      ->orWhere('country', 'ILIKE', "%{$search}%");
                });
            }

            // Apply country filter if provided
            if ($request->has('country')) {
                $query->where('country', 'ILIKE', "%{$request->input('country')}%");
            }

            // Apply state filter if provided
            if ($request->has('state')) {
                $query->where('state', 'ILIKE', "%{$request->input('state')}%");
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'state', 'country', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
            }

            // Apply pagination
            $perPage = min($request->input('per_page', 15), 100); // Max 100 per page
            $cities = $query->paginate($perPage);

            return $this->successResponse([
                'cities' => CityResource::collection($cities->items()),
                'pagination' => [
                    'current_page' => $cities->currentPage(),
                    'per_page' => $cities->perPage(),
                    'total' => $cities->total(),
                    'last_page' => $cities->lastPage(),
                    'has_more_pages' => $cities->hasMorePages(),
                ]
            ], 'Cities retrieved successfully');

        } catch (Exception $e) {
            $this->logError('Failed to retrieve cities', $e, $request->all());
            return $this->errorResponse('Failed to retrieve cities', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $city = City::with(['zones.locations', 'airports'])->findOrFail($id);
            
            return $this->successResponse([
                'city' => new CityResource($city)
            ], 'City retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('City not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to retrieve city', $e, ['city_id' => $id]);
            return $this->errorResponse('Failed to retrieve city', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string|max:500',
                'active' => 'boolean',
            ]);

            $city = City::create($validated);
            
            return $this->successResponse([
                'city' => new CityResource($city)
            ], 'City created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to create city', $e, $request->all());
            return $this->errorResponse('Failed to create city', 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'state' => 'sometimes|required|string|max:255',
                'country' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string|max:500',
                'active' => 'boolean',
            ]);

            $city->update($validated);
            
            return $this->successResponse([
                'city' => new CityResource($city->fresh())
            ], 'City updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('City not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to update city', $e, array_merge($request->all(), ['city_id' => $id]));
            return $this->errorResponse('Failed to update city', 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);
            
            // Check if city has dependent zones or airports
            $zonesCount = $city->zones()->count();
            $airportsCount = $city->airports()->count();
            
            if ($zonesCount > 0 || $airportsCount > 0) {
                return $this->errorResponse(
                    'Cannot delete city. It has associated zones or airports. Please remove them first.',
                    409
                );
            }
            
            $city->delete();
            
            return $this->successResponse(null, 'City deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('City not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to delete city', $e, ['city_id' => $id]);
            return $this->errorResponse('Failed to delete city', 500);
        }
    }

    /**
     * Get all rates from a city to all locations
     */
    public function getCityRates(Request $request, int $cityId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type_id' => 'nullable|exists:service_types,id',
                'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
                'date' => 'nullable|date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Verify city exists
            $city = City::findOrFail($cityId);

            // Get all locations in the city (through zones)
            $cityLocations = Location::whereHas('zone', function ($query) use ($cityId) {
                $query->where('city_id', $cityId);
            })->pluck('id');

            if ($cityLocations->isEmpty()) {
                return $this->successResponse([
                    'city' => new CityResource($city),
                    'rates' => [],
                    'pagination' => null,
                ], 'No locations found for this city');
            }

            // Build rates query
            $query = Rate::with([
                'serviceType', 
                'vehicleType', 
                'fromLocation.zone.city', 
                'toLocation.zone.city',
                'fromZone.city',
                'toZone.city'
            ]);

            // Filter by locations in the city (either as from_location or from zones in the city)
            $query->where(function ($q) use ($cityLocations, $cityId) {
                // Location-specific rates from this city
                $q->whereIn('from_location_id', $cityLocations)
                  // Or zone-based rates from zones in this city
                  ->orWhereHas('fromZone', function ($zoneQuery) use ($cityId) {
                      $zoneQuery->where('city_id', $cityId);
                  });
            });

            // Apply filters
            if (!empty($validated['service_type_id'])) {
                $query->where('service_type_id', $validated['service_type_id']);
            }

            if (!empty($validated['vehicle_type_id'])) {
                $query->where('vehicle_type_id', $validated['vehicle_type_id']);
            }

            // Apply date filter
            $date = $validated['date'] ?? now();
            $query->valid($date);

            // Apply sorting - prioritize location-specific rates, then by price
            $query->orderByRaw('CASE WHEN from_location_id IS NOT NULL THEN 0 ELSE 1 END')
                  ->orderBy('total_one_way', 'asc');

            // Apply pagination
            $perPage = $validated['per_page'] ?? 15;
            $rates = $query->paginate($perPage);

            // Format rates response
            $formattedRates = collect($rates->items())->map(function ($rate) {
                return $this->formatCityRateResponse($rate);
            });

            return $this->successResponse([
                'city' => new CityResource($city),
                'rates' => $formattedRates,
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                    'last_page' => $rates->lastPage(),
                    'has_more_pages' => $rates->hasMorePages(),
                ]
            ], 'City rates retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('City not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to get city rates', $e, array_merge($request->all(), ['city_id' => $cityId]));
            return $this->errorResponse('Failed to get city rates', 500);
        }
    }

    /**
     * Format rate response for city rates endpoint
     */
    private function formatCityRateResponse(Rate $rate): array
    {
        $response = [
            'id' => $rate->id,
            'service_type' => [
                'id' => $rate->serviceType->id,
                'name' => $rate->serviceType->name,
                'code' => $rate->serviceType->code,
            ],
            'vehicle_type' => [
                'id' => $rate->vehicleType->id,
                'name' => $rate->vehicleType->name,
                'code' => $rate->vehicleType->code,
            ],
            'pricing_type' => $rate->isLocationSpecific() ? 'location' : 'zone',
            'cost_vehicle_one_way' => $rate->cost_vehicle_one_way,
            'total_one_way' => $rate->total_one_way,
            'cost_vehicle_round_trip' => $rate->cost_vehicle_round_trip,
            'total_round_trip' => $rate->total_round_trip,
            'num_vehicles' => $rate->num_vehicles,
            'available' => $rate->available,
            'valid_from' => $rate->valid_from?->toISOString(),
            'valid_to' => $rate->valid_to?->toISOString(),
        ];

        // Add origin information (from city)
        if ($rate->isLocationSpecific() && $rate->fromLocation) {
            $response['from'] = [
                'type' => 'location',
                'location_id' => $rate->fromLocation->id,
                'location_name' => $rate->fromLocation->name,
                'zone_name' => $rate->fromLocation->zone->name,
                'city_name' => $rate->fromLocation->zone->city->name,
            ];
        } elseif ($rate->isZoneBased() && $rate->fromZone) {
            $response['from'] = [
                'type' => 'zone',
                'zone_id' => $rate->fromZone->id,
                'zone_name' => $rate->fromZone->name,
                'city_name' => $rate->fromZone->city->name,
            ];
        }

        // Add destination information
        if ($rate->isLocationSpecific() && $rate->toLocation) {
            $response['to'] = [
                'type' => 'location',
                'location_id' => $rate->toLocation->id,
                'location_name' => $rate->toLocation->name,
                'zone_name' => $rate->toLocation->zone->name,
                'city_name' => $rate->toLocation->zone->city->name,
            ];
        } elseif ($rate->isZoneBased() && $rate->toZone) {
            $response['to'] = [
                'type' => 'zone',
                'zone_id' => $rate->toZone->id,
                'zone_name' => $rate->toZone->name,
                'city_name' => $rate->toZone->city->name,
            ];
        }

        return $response;
    }
}
