<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\VehicleTypeResource;
use App\Models\VehicleType;
use App\Models\ServiceFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class VehicleTypeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = VehicleType::with('serviceFeatures');

            // Apply search filter if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('code', 'ILIKE', "%{$search}%");
                });
            }

            // Apply active filter
            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'code', 'max_pax', 'max_units', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
            }

            // Apply pagination
            $perPage = min($request->input('per_page', 15), 100);
            $vehicleTypes = $query->paginate($perPage);

            return $this->successResponse([
                'vehicle_types' => VehicleTypeResource::collection($vehicleTypes->items()),
                'pagination' => [
                    'current_page' => $vehicleTypes->currentPage(),
                    'per_page' => $vehicleTypes->perPage(),
                    'total' => $vehicleTypes->total(),
                    'last_page' => $vehicleTypes->lastPage(),
                    'has_more_pages' => $vehicleTypes->hasMorePages(),
                ]
            ], 'Vehicle types retrieved successfully');

        } catch (Exception $e) {
            $this->logError('Failed to retrieve vehicle types', $e, $request->all());
            return $this->errorResponse('Failed to retrieve vehicle types', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $vehicleType = VehicleType::with('serviceFeatures')->findOrFail($id);
            
            return $this->successResponse([
                'vehicle_type' => new VehicleTypeResource($vehicleType)
            ], 'Vehicle type retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Vehicle type not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to retrieve vehicle type', $e, ['vehicle_type_id' => $id]);
            return $this->errorResponse('Failed to retrieve vehicle type', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10',
                'image' => 'nullable|string|max:255',
                'max_units' => 'required|integer|min:1',
                'max_pax' => 'required|integer|min:1',
                'travel_time' => 'nullable|string|max:255',
                'video_url' => 'nullable|url|max:500',
                'frame' => 'nullable|string|max:255',
                'active' => 'boolean',
                'service_feature_ids' => 'nullable|array',
                'service_feature_ids.*' => 'exists:service_features,id',
            ]);

            $serviceFeatureIds = $validated['service_feature_ids'] ?? [];
            unset($validated['service_feature_ids']);

            $vehicleType = VehicleType::create($validated);
            
            if (!empty($serviceFeatureIds)) {
                $vehicleType->serviceFeatures()->attach($serviceFeatureIds);
            }
            
            $vehicleType->load('serviceFeatures');
            
            return $this->successResponse([
                'vehicle_type' => new VehicleTypeResource($vehicleType)
            ], 'Vehicle type created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to create vehicle type', $e, $request->all());
            return $this->errorResponse('Failed to create vehicle type', 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $vehicleType = VehicleType::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:10',
                'image' => 'nullable|string|max:255',
                'max_units' => 'sometimes|required|integer|min:1',
                'max_pax' => 'sometimes|required|integer|min:1',
                'travel_time' => 'nullable|string|max:255',
                'video_url' => 'nullable|url|max:500',
                'frame' => 'nullable|string|max:255',
                'active' => 'boolean',
                'service_feature_ids' => 'nullable|array',
                'service_feature_ids.*' => 'exists:service_features,id',
            ]);

            $serviceFeatureIds = $validated['service_feature_ids'] ?? null;
            unset($validated['service_feature_ids']);

            $vehicleType->update($validated);
            
            if ($serviceFeatureIds !== null) {
                $vehicleType->serviceFeatures()->sync($serviceFeatureIds);
            }
            
            $vehicleType->load('serviceFeatures');
            
            return $this->successResponse([
                'vehicle_type' => new VehicleTypeResource($vehicleType)
            ], 'Vehicle type updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Vehicle type not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to update vehicle type', $e, array_merge($request->all(), ['vehicle_type_id' => $id]));
            return $this->errorResponse('Failed to update vehicle type', 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $vehicleType = VehicleType::findOrFail($id);
            
            // Check if vehicle type has rates
            $ratesCount = $vehicleType->rates()->count();
            
            if ($ratesCount > 0) {
                return $this->errorResponse(
                    'Cannot delete vehicle type. It has associated rates. Please remove them first.',
                    409
                );
            }
            
            $vehicleType->delete();
            
            return $this->successResponse(null, 'Vehicle type deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Vehicle type not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to delete vehicle type', $e, ['vehicle_type_id' => $id]);
            return $this->errorResponse('Failed to delete vehicle type', 500);
        }
    }
}