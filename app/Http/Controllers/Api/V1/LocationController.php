<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\City;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $locations = Location::with(['city'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }

    public function store(Request $request): JsonResponse|LocationResource
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'type' => 'required|string|max:10',
            'active' => 'boolean',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location = Location::create($validator->validated());

        return new LocationResource($location->load('city'));
    }

    public function show(Location $location): LocationResource
    {
        $location->load(['city']);
        
        return new LocationResource($location);
    }

    public function update(Request $request, Location $location): JsonResponse|LocationResource
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'city_id' => 'exists:cities,id',
            'type' => 'string|max:10',
            'active' => 'boolean',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location->update($validator->validated());

        return new LocationResource($location->load('city'));
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully'
        ]);
    }

    public function byCity(City $city): AnonymousResourceCollection
    {
        $locations = $city->activeLocations()
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }

    public function byType(string $type): AnonymousResourceCollection
    {
        $locations = Location::with(['city'])
            ->where('type', $type)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }
}
