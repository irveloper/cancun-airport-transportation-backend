<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\CityResource;
use App\Models\City;
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
}
