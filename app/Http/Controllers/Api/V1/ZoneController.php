<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ZoneResource;
use App\Models\City;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $zones = Zone::with(['city'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return ZoneResource::collection($zones);
    }

    public function store(Request $request): JsonResponse|ZoneResource
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $zone = Zone::create($validator->validated());

        return new ZoneResource($zone->load('city'));
    }

    public function show(Zone $zone): ZoneResource
    {
        $zone->load(['city']);
        
        return new ZoneResource($zone);
    }

    public function update(Request $request, Zone $zone): JsonResponse|ZoneResource
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'city_id' => 'exists:cities,id',
            'active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $zone->update($validator->validated());

        return new ZoneResource($zone->load('city'));
    }

    public function destroy(Zone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Zone deleted successfully'
        ]);
    }

    public function byCity(City $city): AnonymousResourceCollection
    {
        $zones = $city->activeZones()
            ->orderBy('name')
            ->get();

        return ZoneResource::collection($zones);
    }
}
