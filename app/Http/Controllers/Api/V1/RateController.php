<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\QuoteResource;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Location;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class RateController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Rate::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation', 'fromZone', 'toZone']);

            // Apply filters
            if ($request->has('service_type_id')) {
                $query->where('service_type_id', $request->input('service_type_id'));
            }

            if ($request->has('vehicle_type_id')) {
                $query->where('vehicle_type_id', $request->input('vehicle_type_id'));
            }

            // Zone-based filters
            if ($request->has('from_zone_id')) {
                $query->where('from_zone_id', $request->input('from_zone_id'));
            }

            if ($request->has('to_zone_id')) {
                $query->where('to_zone_id', $request->input('to_zone_id'));
            }

            // Location-based filters
            if ($request->has('from_location_id')) {
                $query->where('from_location_id', $request->input('from_location_id'));
            }

            if ($request->has('to_location_id')) {
                $query->where('to_location_id', $request->input('to_location_id'));
            }

            if ($request->has('available')) {
                $query->where('available', $request->boolean('available'));
            }

            // Filter by rate type
            if ($request->has('rate_type')) {
                if ($request->input('rate_type') === 'zone') {
                    $query->whereNotNull('from_zone_id')->whereNotNull('to_zone_id');
                } elseif ($request->input('rate_type') === 'location') {
                    $query->whereNotNull('from_location_id')->whereNotNull('to_location_id');
                }
            }

            // Apply date filters
            if ($request->has('valid_date')) {
                $query->valid($request->input('valid_date'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            if (in_array($sortBy, ['total_one_way', 'total_round_trip', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
            }

            // Apply pagination
            $perPage = min($request->input('per_page', 15), 100);
            $rates = $query->paginate($perPage);

            return $this->successResponse([
                'rates' => collect($rates->items())->map(function ($rate) {
                    return $this->formatRateResponse($rate);
                }),
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                    'last_page' => $rates->lastPage(),
                    'has_more_pages' => $rates->hasMorePages(),
                ]
            ], 'Rates retrieved successfully');

        } catch (Exception $e) {
            $this->logError('Failed to retrieve rates', $e, $request->all());
            return $this->errorResponse('Failed to retrieve rates', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $rate = Rate::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation', 'fromZone', 'toZone'])->findOrFail($id);
            
            return $this->successResponse([
                'rate' => $this->formatRateResponse($rate)
            ], 'Rate retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Rate not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to retrieve rate', $e, ['rate_id' => $id]);
            return $this->errorResponse('Failed to retrieve rate', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type_id' => 'required|exists:service_types,id',
                'vehicle_type_id' => 'required|exists:vehicle_types,id',
                
                // Zone-based pricing (primary method)
                'from_zone_id' => 'required|exists:zones,id',
                'to_zone_id' => 'required|exists:zones,id',
                
                // Location-specific pricing (optional overrides)
                'from_location_id' => 'nullable|exists:locations,id',
                'to_location_id' => 'nullable|exists:locations,id',
                
                'cost_vehicle_one_way' => 'required|numeric|min:0',
                'total_one_way' => 'required|numeric|min:0',
                'cost_vehicle_round_trip' => 'required|numeric|min:0',
                'total_round_trip' => 'required|numeric|min:0',
                'num_vehicles' => 'required|integer|min:1',
                'available' => 'boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
            ]);

            // If location overrides are provided, validate they belong to the specified zones
            if (!empty($validated['from_location_id'])) {
                $fromLocation = \App\Models\Location::find($validated['from_location_id']);
                if ($fromLocation && $fromLocation->zone_id != $validated['from_zone_id']) {
                    return $this->errorResponse('From location must belong to the specified from zone', 422);
                }
            }

            if (!empty($validated['to_location_id'])) {
                $toLocation = \App\Models\Location::find($validated['to_location_id']);
                if ($toLocation && $toLocation->zone_id != $validated['to_zone_id']) {
                    return $this->errorResponse('To location must belong to the specified to zone', 422);
                }
            }

            $rate = Rate::create($validated);
            $rate->load(['serviceType', 'vehicleType', 'fromLocation', 'toLocation', 'fromZone', 'toZone']);
            
            return $this->successResponse([
                'rate' => $this->formatRateResponse($rate)
            ], 'Rate created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to create rate', $e, $request->all());
            return $this->errorResponse('Failed to create rate', 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $rate = Rate::findOrFail($id);
            
            $validated = $request->validate([
                'service_type_id' => 'sometimes|required|exists:service_types,id',
                'vehicle_type_id' => 'sometimes|required|exists:vehicle_types,id',
                
                // Zone-based pricing (required)
                'from_zone_id' => 'sometimes|required|exists:zones,id',
                'to_zone_id' => 'sometimes|required|exists:zones,id',
                
                // Location-specific pricing (optional overrides)
                'from_location_id' => 'nullable|exists:locations,id',
                'to_location_id' => 'nullable|exists:locations,id',
                
                'cost_vehicle_one_way' => 'sometimes|required|numeric|min:0',
                'total_one_way' => 'sometimes|required|numeric|min:0',
                'cost_vehicle_round_trip' => 'sometimes|required|numeric|min:0',
                'total_round_trip' => 'sometimes|required|numeric|min:0',
                'num_vehicles' => 'sometimes|required|integer|min:1',
                'available' => 'boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
            ]);

            // Validate location overrides belong to specified zones
            $fromZoneId = $validated['from_zone_id'] ?? $rate->from_zone_id;
            $toZoneId = $validated['to_zone_id'] ?? $rate->to_zone_id;

            if (isset($validated['from_location_id']) && !empty($validated['from_location_id'])) {
                $fromLocation = \App\Models\Location::find($validated['from_location_id']);
                if ($fromLocation && $fromLocation->zone_id != $fromZoneId) {
                    return $this->errorResponse('From location must belong to the specified from zone', 422);
                }
            }

            if (isset($validated['to_location_id']) && !empty($validated['to_location_id'])) {
                $toLocation = \App\Models\Location::find($validated['to_location_id']);
                if ($toLocation && $toLocation->zone_id != $toZoneId) {
                    return $this->errorResponse('To location must belong to the specified to zone', 422);
                }
            }

            $rate->update($validated);
            $rate->load(['serviceType', 'vehicleType', 'fromLocation', 'toLocation', 'fromZone', 'toZone']);
            
            return $this->successResponse([
                'rate' => $this->formatRateResponse($rate)
            ], 'Rate updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Rate not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to update rate', $e, array_merge($request->all(), ['rate_id' => $id]));
            return $this->errorResponse('Failed to update rate', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $rate = Rate::findOrFail($id);
            $rate->delete();
            
            return $this->successResponse(null, 'Rate deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Rate not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to delete rate', $e, ['rate_id' => $id]);
            return $this->errorResponse('Failed to delete rate', 500);
        }
    }

    /**
     * Get rates for a specific route (from location to location)
     */
    public function getRouteRates(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type_id' => 'required|exists:service_types,id',
                'from_location_id' => 'required|exists:locations,id',
                'to_location_id' => 'required|exists:locations,id',
                'date' => 'nullable|date',
            ]);

            $rates = Rate::findForRoute(
                $validated['service_type_id'],
                $validated['from_location_id'],
                $validated['to_location_id'],
                $validated['date'] ?? now()
            );

            return $this->successResponse([
                'rates' => $rates->map(function ($rate) {
                    return $this->formatRateResponse($rate);
                })
            ], 'Route rates retrieved successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to get route rates', $e, $request->all());
            return $this->errorResponse('Failed to get route rates', 500);
        }
    }

    /**
     * Get rates for a zone-to-zone route
     */
    public function getZoneRates(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_type_id' => 'required|exists:service_types,id',
                'from_zone_id' => 'required|exists:zones,id',
                'to_zone_id' => 'required|exists:zones,id',
                'date' => 'nullable|date',
            ]);

            $rates = Rate::with(['vehicleType.serviceFeatures'])
                        ->where('service_type_id', $validated['service_type_id'])
                        ->where('from_zone_id', $validated['from_zone_id'])
                        ->where('to_zone_id', $validated['to_zone_id'])
                        ->valid($validated['date'] ?? now())
                        ->get();

            return $this->successResponse([
                'rates' => $rates->map(function ($rate) {
                    return $this->formatRateResponse($rate);
                })
            ], 'Zone rates retrieved successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to get zone rates', $e, $request->all());
            return $this->errorResponse('Failed to get zone rates', 500);
        }
    }

    /**
     * Format rate response consistently
     */
    private function formatRateResponse(Rate $rate): array
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
            'cost_vehicle_one_way' => $rate->cost_vehicle_one_way,
            'total_one_way' => $rate->total_one_way,
            'cost_vehicle_round_trip' => $rate->cost_vehicle_round_trip,
            'total_round_trip' => $rate->total_round_trip,
            'num_vehicles' => $rate->num_vehicles,
            'available' => $rate->available,
            'valid_from' => $rate->valid_from?->toISOString(),
            'valid_to' => $rate->valid_to?->toISOString(),
            'created_at' => $rate->created_at->toISOString(),
            'updated_at' => $rate->updated_at->toISOString(),
        ];

        // Add zone information if zone-based pricing
        if ($rate->isZoneBased()) {
            $response['from_zone'] = [
                'id' => $rate->fromZone->id,
                'name' => $rate->fromZone->name,
            ];
            $response['to_zone'] = [
                'id' => $rate->toZone->id,
                'name' => $rate->toZone->name,
            ];
            $response['pricing_type'] = 'zone';
        }

        // Add location information if location-specific pricing
        if ($rate->isLocationSpecific()) {
            $response['from_location'] = [
                'id' => $rate->fromLocation->id,
                'name' => $rate->fromLocation->name,
            ];
            $response['to_location'] = [
                'id' => $rate->toLocation->id,
                'name' => $rate->toLocation->name,
            ];
            $response['pricing_type'] = 'location';
        }

        return $response;
    }
}