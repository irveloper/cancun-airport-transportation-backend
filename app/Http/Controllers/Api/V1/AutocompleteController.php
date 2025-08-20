<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Airport;
use App\Models\Zone;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AutocompleteController extends BaseApiController
{
    /**
     * Search for locations based on service type and input parameters
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'lang' => 'required|string|size:2',
                'type' => 'required|string|in:round-trip,arrival,departure,transfer-one-way,transfer-round-trip',
                'input' => 'required|string|in:from,to',
                'q' => 'nullable|string|max:255',
                'from' => 'nullable|string',
                'start' => 'nullable|string'
            ], [
                'lang.required' => 'Language parameter is required',
                'lang.size' => 'Language must be a 2-character code (e.g., "en", "es")',
                'type.required' => 'Service type is required',
                'type.in' => 'Service type must be one of: round-trip, arrival, departure, transfer-one-way, transfer-round-trip',
                'input.required' => 'Input field is required',
                'input.in' => 'Input must be either "from" or "to"',
                'q.max' => 'Search query must not exceed 255 characters'
            ]);

            $type = $validated['type'];
            $input = $validated['input'];
            $query = $validated['q'] ?? '';
            $fromId = $validated['from'] ?? null;

            // Log the search request for monitoring
            Log::info('Autocomplete search', [
                'type' => $type,
                'input' => $input,
                'query' => $query,
                'from_id' => $fromId,
                'ip' => $request->ip()
            ]);

            $result = match($type) {
                'round-trip' => $this->handleRoundTrip($input, $query, $fromId),
                'arrival' => $this->handleArrival($input, $query, $fromId),
                'departure' => $this->handleDeparture($input, $query, $fromId),
                'transfer-one-way' => $this->handleTransferOneWay($input, $query, $fromId),
                'transfer-round-trip' => $this->handleTransferRoundTrip($input, $query, $fromId),
            };

            return $this->successResponse($result->getData(), 'Search completed successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Autocomplete search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return $this->serverErrorResponse('An unexpected error occurred while searching');
        }
    }


    private function handleRoundTrip(string $input, string $query, ?string $fromId): JsonResponse
    {
        if ($input === 'from') {
            return $this->getAirportSuggestions($query);
        } else {
            return $this->getDestinationSuggestions($query, $fromId);
        }
    }

    private function handleArrival(string $input, string $query, ?string $fromId): JsonResponse
    {
        if ($input === 'from') {
            return $this->getAirportSuggestions($query);
        } else {
            return $this->getDestinationSuggestions($query, $fromId);
        }
    }

    private function handleDeparture(string $input, string $query, ?string $fromId): JsonResponse
    {
        if ($input === 'from') {
            return $this->getHotelAndLocationSuggestions($query);
        } else {
            return $this->getAirportSuggestions($query);
        }
    }

    private function handleTransferOneWay(string $input, string $query, ?string $fromId): JsonResponse
    {
        return $this->getAllSuggestions($query, $fromId);
    }

    private function handleTransferRoundTrip(string $input, string $query, ?string $fromId): JsonResponse
    {
        return $this->getAllSuggestions($query, $fromId);
    }

    private function getAirportSuggestions(string $query): JsonResponse
    {
        try {
            $airports = Airport::with('city')
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%")
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->limit(10)
                ->get()
                ->map(function($airport) {
                    return [
                        'id' => (string)$airport->id,
                        'name' => $airport->name,
                        'city' => $airport->city->name
                    ];
                });

            $data = [
                'airport' => $airports,
                'zones' => [],
                'locations' => []
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching airport suggestions', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'airport' => [],
                'zones' => [],
                'locations' => []
            ]);
        }
    }

    private function getDestinationSuggestions(string $query, ?string $fromId): JsonResponse
    {
        try {
            $zones = Zone::with('city')
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->limit(20)
                ->get()
                ->map(function($zone) {
                    return [
                        'id' => (string)$zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city->name ?? ''
                    ];
                });

            $locationsGrouped = $this->getGroupedLocations($query);

            return response()->json([
                'airport' => [],
                'zones' => $zones,
                'locations' => $locationsGrouped
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching destination suggestions', [
                'query' => $query,
                'from_id' => $fromId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'airport' => [],
                'zones' => [],
                'locations' => []
            ]);
        }
    }

    private function getHotelAndLocationSuggestions(string $query): JsonResponse
    {
        try {
            $zones = Zone::with('city')
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->limit(20)
                ->get()
                ->map(function($zone) {
                    return [
                        'id' => (string)$zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city->name ?? ''
                    ];
                });

            $locationsGrouped = $this->getGroupedLocations($query);

            return response()->json([
                'airport' => [],
                'zones' => $zones,
                'locations' => $locationsGrouped
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching hotel and location suggestions', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'airport' => [],
                'zones' => [],
                'locations' => []
            ]);
        }
    }

    private function getAllSuggestions(string $query, ?string $fromId): JsonResponse
    {
        try {
            $airports = Airport::with('city')
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%")
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->limit(5)
                ->get()
                ->map(function($airport) {
                    return [
                        'id' => (string)$airport->id,
                        'name' => $airport->name,
                        'city' => $airport->city->name
                    ];
                });

            $zones = Zone::with('city')
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->limit(15)
                ->get()
                ->map(function($zone) {
                    return [
                        'id' => (string)$zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city->name ?? ''
                    ];
                });

            $locationsGrouped = $this->getGroupedLocations($query);

            return response()->json([
                'airport' => $airports,
                'zones' => $zones,
                'locations' => $locationsGrouped
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching all suggestions', [
                'query' => $query,
                'from_id' => $fromId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'airport' => [],
                'zones' => [],
                'locations' => []
            ]);
        }
    }

    private function getGroupedLocations(string $query): array
    {
        try {
            if (empty(trim($query))) {
                return [];
            }

            $locations = Location::with(['zone.city'])
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('address', 'like', "%{$query}%")
                      ->orWhereHas('zone', function($zoneQuery) use ($query) {
                          $zoneQuery->where('name', 'like', "%{$query}%")
                                   ->orWhereHas('city', function($cityQuery) use ($query) {
                                       $cityQuery->where('name', 'like', "%{$query}%");
                                   });
                      });
                })
                ->limit(50)
                ->get();

            $grouped = [];
            foreach ($locations as $location) {
                $cityId = $location->zone->city_id ?? null;
                $cityName = $location->zone->city->name ?? 'Unknown';
                
                if (!isset($grouped[$cityId])) {
                    $grouped[$cityId] = [
                        'name' => $cityName,
                        'locations' => []
                    ];
                }
                
                $grouped[$cityId]['locations'][] = [
                    'id' => (string)$location->id,
                    'name' => $location->name,
                    'type' => $location->type,
                    'city' => $cityName
                ];
            }

            return $grouped;
        } catch (\Exception $e) {
            Log::error('Error grouping locations', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}
