<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Location;
use App\Models\Zone;
use App\Models\City;
use App\Models\Airport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AutocompleteController extends BaseApiController
{
    /**
     * Search for locations based on query parameters
     * 
     * Supports the following parameters:
     * - lang: Language code (en, es) - optional
     * - type: Service type (departure, arrival, etc.) - optional
     * - input: Input type (from, to) - optional
     * - q: Search query - the main search parameter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Validate request parameters - all optional except for search query
            $validated = $request->validate([
                'lang' => 'nullable|string|size:2|in:en,es',
                'type' => 'nullable|string|in:departure,arrival,round-trip,one-way,hotel-to-hotel',
                'input' => 'nullable|string|in:from,to',
                'q' => 'nullable|string|max:255',
                'query' => 'nullable|string|max:255', // Alternative parameter name
                'limit' => 'nullable|integer|min:1|max:100',
            ], [
                'lang.size' => 'Language must be a 2-character code (e.g., "en", "es")',
                'lang.in' => 'Language must be either "en" or "es"',
                'type.in' => 'Service type must be one of: departure, arrival, round-trip, one-way, hotel-to-hotel',
                'input.in' => 'Input must be either "from" or "to"',
                'q.max' => 'Search query must not exceed 255 characters',
                'query.max' => 'Search query must not exceed 255 characters',
                'limit.min' => 'Limit must be at least 1',
                'limit.max' => 'Limit must not exceed 100',
            ]);

            // Get search query from either 'q' or 'query' parameter and sanitize it
            $searchQuery = htmlspecialchars(strip_tags($validated['q'] ?? $validated['query'] ?? ''), ENT_QUOTES, 'UTF-8');
            $lang = $validated['lang'] ?? 'en';
            $type = $validated['type'] ?? 'departure';
            $input = $validated['input'] ?? 'to';
            $limit = $validated['limit'] ?? 20;

            // If no search query provided, return empty results
            if (empty(trim($searchQuery))) {
                return $this->successResponse([
                    'airport' => [],
                    'zones' => [],
                    'locations' => [],
                    'meta' => [
                        'query' => '',
                        'lang' => $lang,
                        'type' => $type,
                        'input' => $input,
                        'search_context' => 'none',
                        'total_results' => 0
                    ]
                ], 'Please provide a search query');
            }

            // Log the search request for monitoring
            Log::info('Autocomplete search', [
                'query' => $searchQuery,
                'lang' => $lang,
                'type' => $type,
                'input' => $input,
                'limit' => $limit,
                'ip' => $request->ip()
            ]);

            // Determine if we should show airports or locations based on service type and input
            $shouldShowAirports = $this->shouldShowAirports($type, $input);
            
            if ($shouldShowAirports) {
                // For airport-based searches
                $airports = $this->searchAirports($searchQuery, $limit);
                $zones = [];
                $groupedLocations = [];
                $searchContext = 'airports';
            } else {
                // For destination searches (hotels, locations)
                $airports = [];
                $zones = $this->searchZones($searchQuery, min($limit, 10));
                $groupedLocations = $this->getGroupedLocations($searchQuery, $limit);
                $searchContext = 'destinations';
            }
            
            // Original grouped structure with smart service-type logic
            $result = [
                'airport' => $airports,
                'zones' => $zones,
                'locations' => $groupedLocations,
                'meta' => [
                    'query' => $searchQuery,
                    'lang' => $lang,
                    'type' => $type,
                    'input' => $input,
                    'search_context' => $searchContext,
                    'total_results' => count($airports) + count($zones) + $this->countGroupedLocations($groupedLocations)
                ]
            ];

            return $this->successResponse($result, 'Search completed successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Autocomplete search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse('An error occurred while searching', 500);
        }
    }

    /**
     * Get grouped locations by city (original structure)
     */
    private function getGroupedLocations(string $query, int $limit): array
    {
        try {
            if (empty(trim($query))) {
                return [];
            }

            $locations = Location::with(['zone.city'])
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereHas('zone', function($zoneQuery) use ($query) {
                          $zoneQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                                   ->orWhereHas('city', function($cityQuery) use ($query) {
                                       $cityQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"]);
                                   });
                      });
                })
                ->orderByRaw("CASE 
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(address) LIKE ? THEN 3
                    ELSE 4
                END", [
                    strtolower($query)."%",
                    "%".strtolower($query)."%",
                    "%".strtolower($query)."%"
                ])
                ->limit($limit)
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
                    'city' => $cityName,
                    'zone' => [
                        'id' => $location->zone->id ?? null,
                        'name' => $location->zone->name ?? null,
                    ]
                ];
            }

            return $grouped;

        } catch (\Exception $e) {
            Log::error('Error getting grouped locations', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Count total locations in grouped structure
     */
    private function countGroupedLocations(array $groupedLocations): int
    {
        $count = 0;
        foreach ($groupedLocations as $group) {
            $count += count($group['locations'] ?? []);
        }
        return $count;
    }

    /**
     * Search locations by name, address, or related zone/city (legacy method)
     */
    private function searchLocations(string $query, int $limit): array
    {
        try {
            $locations = Location::with(['zone.city'])
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereHas('zone', function($zoneQuery) use ($query) {
                          $zoneQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                                   ->orWhereHas('city', function($cityQuery) use ($query) {
                                       $cityQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"]);
                                   });
                      });
                })
                ->orderByRaw("CASE 
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(address) LIKE ? THEN 3
                    ELSE 4
                END", [
                    strtolower($query)."%",
                    "%".strtolower($query)."%",
                    "%".strtolower($query)."%"
                ])
                ->limit($limit)
                ->get()
                ->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->type,
                        'address' => $location->address,
                        'zone' => [
                            'id' => $location->zone->id ?? null,
                            'name' => $location->zone->name ?? null,
                        ],
                        'city' => [
                            'id' => $location->zone->city->id ?? null,
                            'name' => $location->zone->city->name ?? null,
                        ],
                        'coordinates' => [
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                        ],
                        'match_type' => 'location'
                    ];
                })
                ->toArray();

            return $locations;

        } catch (\Exception $e) {
            Log::error('Error searching locations', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Search zones by name or related city
     */
    private function searchZones(string $query, int $limit): array
    {
        try {
            $zones = Zone::with(['city', 'locations'])
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"]);
                      });
                })
                ->orderByRaw("CASE 
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    ELSE 3
                END", [
                    strtolower($query)."%",
                    "%".strtolower($query)."%"
                ])
                ->limit($limit)
                ->get()
                ->map(function($zone) {
                    return [
                        'id' => (string)$zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city->name ?? null
                    ];
                })
                ->toArray();

            return $zones;

        } catch (\Exception $e) {
            Log::error('Error searching zones', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Search cities by name
     */
    private function searchCities(string $query, int $limit): array
    {
        try {
            $cities = City::where(function($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereRaw('LOWER(state) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereRaw('LOWER(country) LIKE ?', ["%".strtolower($query)."%"]);
                })
                ->orderByRaw("CASE 
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    ELSE 3
                END", [
                    strtolower($query)."%",
                    "%".strtolower($query)."%"
                ])
                ->limit($limit)
                ->get()
                ->map(function($city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'state' => $city->state,
                        'country' => $city->country,
                        'match_type' => 'city'
                    ];
                })
                ->toArray();

            return $cities;

        } catch (\Exception $e) {
            Log::error('Error searching cities', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Determine if we should show airports based on service type and input
     */
    private function shouldShowAirports(string $type, string $input): bool
    {
        // Airport-based scenarios:
        // 1. departure + from = Airport (leaving FROM airport)
        // 2. arrival + to = Airport (arriving TO airport)  
        // 3. round-trip + from = Airport (starting FROM airport)
        
        return match($type . '_' . $input) {
            'departure_from' => true,  // Departing from airport
            'arrival_to' => true,      // Arriving to airport
            'round-trip_from' => true, // Round trip starting from airport
            default => false           // All other cases show destinations (hotels/locations)
        };
    }

    /**
     * Search airports by name, code, or city
     */
    private function searchAirports(string $query, int $limit): array
    {
        try {
            $airports = Airport::with(['city'])
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereRaw('LOWER(code) LIKE ?', ["%".strtolower($query)."%"])
                      ->orWhereHas('city', function($cityQuery) use ($query) {
                          $cityQuery->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"]);
                      });
                })
                ->orderByRaw("CASE 
                    WHEN LOWER(code) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(name) LIKE ? THEN 3
                    ELSE 4
                END", [
                    strtolower($query)."%",
                    strtolower($query)."%",
                    "%".strtolower($query)."%"
                ])
                ->limit($limit)
                ->get()
                ->map(function($airport) {
                    return [
                        'id' => (string)$airport->id,
                        'name' => $airport->name,
                        'code' => $airport->code,
                        'city' => $airport->city->name ?? null
                    ];
                })
                ->toArray();

            return $airports;

        } catch (\Exception $e) {
            Log::error('Error searching airports', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}