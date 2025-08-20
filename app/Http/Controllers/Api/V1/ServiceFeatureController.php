<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ServiceFeatureResource;
use App\Models\ServiceFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ServiceFeatureController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceFeature::query();

            // Apply search filter if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'ILIKE', "%{$search}%")
                      ->orWhere('name_es', 'ILIKE', "%{$search}%");
                });
            }

            // Apply active filter
            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'sort_order');
            $sortOrder = $request->input('sort_order', 'asc');
            
            if (in_array($sortBy, ['sort_order', 'name_en', 'name_es', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
            }

            // Apply pagination
            $perPage = min($request->input('per_page', 15), 100);
            $features = $query->paginate($perPage);

            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';

            return $this->successResponse([
                'features' => collect($features->items())->map(function ($feature) use ($locale) {
                    return new ServiceFeatureResource($feature, $locale);
                }),
                'pagination' => [
                    'current_page' => $features->currentPage(),
                    'per_page' => $features->perPage(),
                    'total' => $features->total(),
                    'last_page' => $features->lastPage(),
                    'has_more_pages' => $features->hasMorePages(),
                ]
            ], 'Service features retrieved successfully');

        } catch (Exception $e) {
            $this->logError('Failed to retrieve service features', $e, $request->all());
            return $this->errorResponse('Failed to retrieve service features', 500);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $feature = ServiceFeature::findOrFail($id);
            
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';
            
            return $this->successResponse([
                'feature' => new ServiceFeatureResource($feature, $locale)
            ], 'Service feature retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Service feature not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to retrieve service feature', $e, ['feature_id' => $id]);
            return $this->errorResponse('Failed to retrieve service feature', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name_en' => 'required|string|max:255',
                'name_es' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_es' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'active' => 'boolean',
            ]);

            $feature = ServiceFeature::create($validated);
            
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';
            
            return $this->successResponse([
                'feature' => new ServiceFeatureResource($feature, $locale)
            ], 'Service feature created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to create service feature', $e, $request->all());
            return $this->errorResponse('Failed to create service feature', 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $feature = ServiceFeature::findOrFail($id);
            
            $validated = $request->validate([
                'name_en' => 'sometimes|required|string|max:255',
                'name_es' => 'sometimes|required|string|max:255',
                'description_en' => 'nullable|string',
                'description_es' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'active' => 'boolean',
            ]);

            $feature->update($validated);
            
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';
            
            return $this->successResponse([
                'feature' => new ServiceFeatureResource($feature->fresh(), $locale)
            ], 'Service feature updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Service feature not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError('Failed to update service feature', $e, array_merge($request->all(), ['feature_id' => $id]));
            return $this->errorResponse('Failed to update service feature', 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $feature = ServiceFeature::findOrFail($id);
            
            // Check if feature is being used by any vehicle types
            $vehicleTypesCount = $feature->vehicleTypes()->count();
            
            if ($vehicleTypesCount > 0) {
                return $this->errorResponse(
                    'Cannot delete service feature. It is being used by vehicle types. Please remove the associations first.',
                    409
                );
            }
            
            $feature->delete();
            
            return $this->successResponse(null, 'Service feature deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Service feature not found', 404);
        } catch (Exception $e) {
            $this->logError('Failed to delete service feature', $e, ['feature_id' => $id]);
            return $this->errorResponse('Failed to delete service feature', 500);
        }
    }
}