<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\QuoteResource;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Location;
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
            $query = Rate::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation']);

            // Apply filters
            if ($request->has('service_type_id')) {
                $query->where('service_type_id', $request->input('service_type_id'));
            }

            if ($request->has('vehicle_type_id')) {
                $query->where('vehicle_type_id', $request->input('vehicle_type_id'));
            }

            if ($request->has('from_location_id')) {
                $query->where('from_location_id', $request->input('from_location_id'));
            }

            if ($request->has('to_location_id')) {
                $query->where('to_location_id', $request->input('to_location_id'));
            }

            if ($request->has('available')) {
                $query->where('available', $request->boolean('available'));
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
                    return [
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
                        'from_location' => [
                            'id' => $rate->fromLocation->id,
                            'name' => $rate->fromLocation->name,
                        ],
                        'to_location' => [
                            'id' => $rate->toLocation->id,
                            'name' => $rate->toLocation->name,
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

    public function show(int $id): JsonResponse
    {
        try {
            $rate = Rate::with(['serviceType', 'vehicleType', 'fromLocation', 'toLocation'])->findOrFail($id);
            
            return $this->successResponse([
                'rate' => [
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
                    'from_location' => [
                        'id' => $rate->fromLocation->id,
                        'name' => $rate->fromLocation->name,
                    ],
                    'to_location' => [
                        'id' => $rate->toLocation->id,
                        'name' => $rate->toLocation->name,
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
                ]
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
                'from_location_id' => 'required|exists:locations,id',
                'to_location_id' => 'required|exists:locations,id',
                'cost_vehicle_one_way' => 'required|numeric|min:0',
                'total_one_way' => 'required|numeric|min:0',
                'cost_vehicle_round_trip' => 'required|numeric|min:0',
                'total_round_trip' => 'required|numeric|min:0',
                'num_vehicles' => 'required|integer|min:1',
                'available' => 'boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
            ]);

            $rate = Rate::create($validated);
            $rate->load(['serviceType', 'vehicleType', 'fromLocation', 'toLocation']);
            
            return $this->successResponse([
                'rate' => [
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
                    'from_location' => [
                        'id' => $rate->fromLocation->id,
                        'name' => $rate->fromLocation->name,
                    ],
                    'to_location' => [
                        'id' => $rate->toLocation->id,
                        'name' => $rate->toLocation->name,
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
                ]
            ], 'Rate created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to create rate', $e, $request->all());
            return $this->errorResponse('Failed to create rate', 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rate = Rate::findOrFail($id);
            
            $validated = $request->validate([
                'service_type_id' => 'sometimes|required|exists:service_types,id',
                'vehicle_type_id' => 'sometimes|required|exists:vehicle_types,id',
                'from_location_id' => 'sometimes|required|exists:locations,id',
                'to_location_id' => 'sometimes|required|exists:locations,id',
                'cost_vehicle_one_way' => 'sometimes|required|numeric|min:0',
                'total_one_way' => 'sometimes|required|numeric|min:0',
                'cost_vehicle_round_trip' => 'sometimes|required|numeric|min:0',
                'total_round_trip' => 'sometimes|required|numeric|min:0',
                'num_vehicles' => 'sometimes|required|integer|min:1',
                'available' => 'boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
            ]);

            $rate->update($validated);
            $rate->load(['serviceType', 'vehicleType', 'fromLocation', 'toLocation']);
            
            return $this->successResponse([
                'rate' => [
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
                    'from_location' => [
                        'id' => $rate->fromLocation->id,
                        'name' => $rate->fromLocation->name,
                    ],
                    'to_location' => [
                        'id' => $rate->toLocation->id,
                        'name' => $rate->toLocation->name,
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
                ]
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

    public function destroy(int $id): JsonResponse
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
}