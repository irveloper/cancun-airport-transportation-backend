<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Rate;
use App\Models\CurrencyExchange;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class HighlightedQuoteController extends BaseApiController
{
    /**
     * Get highlighted/featured quotes for the homepage
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'currency' => 'nullable|string|in:USD,MXN',
                'locale' => 'nullable|string|in:en,es',
            ]);

            $currency = strtoupper($validated['currency'] ?? 'USD');
            $locale = $validated['locale'] ?? 'en';

            // For demo purposes, always return mock data until database is properly set up
            return $this->getMockHighlightedQuotes($currency, $locale);

            // Get exchange rates
            $exchangeRateToUSD = CurrencyExchange::getExchangeRate($currency, 'USD');
            $exchangeRateToMXN = CurrencyExchange::getExchangeRate($currency, 'MXN');
            $exchangeRate = CurrencyExchange::getExchangeRate('USD', $currency);

            $response = [
                'currency' => strtolower($currency),
                'exchange_rates' => [
                    'to_usd' => number_format($exchangeRateToUSD, 6),
                    'to_mxn' => number_format($exchangeRateToMXN, 6),
                ],
                'highlighted_quotes' => []
            ];

            foreach ($highlightedRates as $rate) {
                $vehicleType = $rate->vehicleType;
                $serviceType = $rate->serviceType;
                $fromLocation = $rate->fromLocation;
                $toLocation = $rate->toLocation;

                // Build features array
                $features = $vehicleType->serviceFeatures->map(function ($feature) use ($locale) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->getName($locale),
                        'description' => $feature->getDescription($locale),
                        'icon' => $feature->icon,
                    ];
                })->toArray();

                // Convert prices to requested currency
                $costOneWay = (float) $rate->cost_vehicle_one_way * $exchangeRate;
                $totalOneWay = (float) $rate->total_one_way * $exchangeRate;
                $costRoundTrip = $rate->cost_vehicle_round_trip ? (float) $rate->cost_vehicle_round_trip * $exchangeRate : null;
                $totalRoundTrip = $rate->total_round_trip ? (float) $rate->total_round_trip * $exchangeRate : null;

                // Determine service category based on locations
                $serviceCategory = $this->determineServiceCategory($fromLocation, $toLocation);

                $response['highlighted_quotes'][] = [
                    'id' => $rate->id,
                    'service_category' => $serviceCategory,
                    'service_type' => [
                        'id' => $serviceType->id,
                        'name' => $serviceType->name,
                        'code' => $serviceType->code,
                    ],
                    'vehicle_type' => [
                        'id' => $vehicleType->id,
                        'name' => $vehicleType->name,
                        'code' => $vehicleType->code,
                        'image' => $vehicleType->image,
                        'max_pax' => $vehicleType->max_pax,
                        'max_units' => $vehicleType->max_units,
                        'travel_time' => $vehicleType->travel_time,
                        'features' => $features,
                    ],
                    'route' => [
                        'from' => [
                            'id' => $fromLocation->id,
                            'name' => $fromLocation->name,
                            'type' => $fromLocation->type,
                            'city' => $fromLocation->zone->city->name ?? null,
                            'city_id' => $fromLocation->zone->city->id ?? null,
                        ],
                        'to' => [
                            'id' => $toLocation->id,
                            'name' => $toLocation->name,
                            'type' => $toLocation->type,
                            'city' => $toLocation->zone->city->name ?? null,
                            'city_id' => $toLocation->zone->city->id ?? null,
                        ],
                    ],
                    'pricing' => [
                        'cost_one_way' => number_format($costOneWay, 2, '.', ''),
                        'total_one_way' => (int) round($totalOneWay),
                        'cost_round_trip' => $costRoundTrip ? number_format($costRoundTrip, 2, '.', '') : null,
                        'total_round_trip' => $totalRoundTrip ? (int) round($totalRoundTrip) : null,
                        'starting_from' => (int) round($totalOneWay),
                    ],
                    'highlight' => [
                        'badge' => $rate->highlight_badge,
                        'description' => $rate->highlight_description,
                    ],
                    'num_vehicles' => $rate->num_vehicles,
                    'available' => $rate->available ? 1 : 0,
                ];
            }

            return $this->resourceResponse('highlighted_quotes', 'retrieved', $response);

        } catch (Exception $e) {
            $this->logError('Failed to get highlighted quotes', $e, $request->all());
            return $this->resourceErrorResponse('highlighted_quotes', 'retrieval_failed', 500);
        }
    }

    /**
     * Get a specific highlighted quote by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'currency' => 'nullable|string|in:USD,MXN',
                'locale' => 'nullable|string|in:en,es',
            ]);

            $currency = strtoupper($validated['currency'] ?? 'USD');
            $locale = $validated['locale'] ?? 'en';

            $rate = Rate::with([
                'serviceType',
                'vehicleType.serviceFeatures',
                'fromLocation.zone.city',
                'toLocation.zone.city'
            ])
            ->where('id', $id)
            ->where('highlighted', true)
            ->where('available', true)
            ->first();

            if (!$rate) {
                return $this->resourceErrorResponse('highlighted_quote', 'not_found', 404);
            }

            // Build the same structure as index but for single item
            $exchangeRate = CurrencyExchange::getExchangeRate('USD', $currency);
            $vehicleType = $rate->vehicleType;
            $serviceType = $rate->serviceType;
            $fromLocation = $rate->fromLocation;
            $toLocation = $rate->toLocation;

            $features = $vehicleType->serviceFeatures->map(function ($feature) use ($locale) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->getName($locale),
                    'description' => $feature->getDescription($locale),
                    'icon' => $feature->icon,
                ];
            })->toArray();

            $costOneWay = (float) $rate->cost_vehicle_one_way * $exchangeRate;
            $totalOneWay = (float) $rate->total_one_way * $exchangeRate;
            $costRoundTrip = $rate->cost_vehicle_round_trip ? (float) $rate->cost_vehicle_round_trip * $exchangeRate : null;
            $totalRoundTrip = $rate->total_round_trip ? (float) $rate->total_round_trip * $exchangeRate : null;

            $serviceCategory = $this->determineServiceCategory($fromLocation, $toLocation);

            $response = [
                'id' => $rate->id,
                'service_category' => $serviceCategory,
                'service_type' => [
                    'id' => $serviceType->id,
                    'name' => $serviceType->name,
                    'code' => $serviceType->code,
                ],
                'vehicle_type' => [
                    'id' => $vehicleType->id,
                    'name' => $vehicleType->name,
                    'code' => $vehicleType->code,
                    'image' => $vehicleType->image,
                    'max_pax' => $vehicleType->max_pax,
                    'max_units' => $vehicleType->max_units,
                    'travel_time' => $vehicleType->travel_time,
                    'video_url' => $vehicleType->video_url,
                    'frame' => $vehicleType->frame,
                    'features' => $features,
                ],
                'route' => [
                    'from' => [
                        'id' => $fromLocation->id,
                        'name' => $fromLocation->name,
                        'type' => $fromLocation->type,
                        'city' => $fromLocation->zone->city->name ?? null,
                        'city_id' => $fromLocation->zone->city->id ?? null,
                    ],
                    'to' => [
                        'id' => $toLocation->id,
                        'name' => $toLocation->name,
                        'type' => $toLocation->type,
                        'city' => $toLocation->zone->city->name ?? null,
                        'city_id' => $toLocation->zone->city->id ?? null,
                    ],
                ],
                'pricing' => [
                    'cost_one_way' => number_format($costOneWay, 2, '.', ''),
                    'total_one_way' => (int) round($totalOneWay),
                    'cost_round_trip' => $costRoundTrip ? number_format($costRoundTrip, 2, '.', '') : null,
                    'total_round_trip' => $totalRoundTrip ? (int) round($totalRoundTrip) : null,
                    'starting_from' => (int) round($totalOneWay),
                    'currency' => strtolower($currency),
                ],
                'highlight' => [
                    'badge' => $rate->highlight_badge,
                    'description' => $rate->highlight_description,
                ],
                'num_vehicles' => $rate->num_vehicles,
                'available' => $rate->available ? 1 : 0,
            ];

            return $this->resourceResponse('highlighted_quote', 'retrieved', $response);

        } catch (Exception $e) {
            $this->logError('Failed to get highlighted quote', $e, ['id' => $id] + $request->all());
            return $this->resourceErrorResponse('highlighted_quote', 'retrieval_failed', 500);
        }
    }

    /**
     * Determine service category based on location types
     */
    private function determineServiceCategory($fromLocation, $toLocation): string
    {
        if ($fromLocation->type === 'A' && $toLocation->type !== 'A') {
            return 'Airport Transfer';
        }

        if ($fromLocation->type !== 'A' && $toLocation->type === 'A') {
            return 'Airport Transfer';
        }

        if ($fromLocation->type === 'A' && $toLocation->type === 'A') {
            return 'Airport Transfer';
        }

        if ($fromLocation->type === 'H' && $toLocation->type === 'H') {
            return 'Hotel Transfer';
        }

        return 'Private Transfer';
    }

    /**
     * Return mock highlighted quotes for demo purposes
     */
    private function getMockHighlightedQuotes(string $currency, string $locale): JsonResponse
    {
        $exchangeRateToUSD = 1.0;
        $exchangeRateToMXN = 18.5;

        if ($currency === 'MXN') {
            $multiplier = 18.5;
        } else {
            $multiplier = 1.0;
        }

        $response = [
            'currency' => strtolower($currency),
            'exchange_rates' => [
                'to_usd' => number_format($exchangeRateToUSD, 6),
                'to_mxn' => number_format($exchangeRateToMXN, 6),
            ],
            'highlighted_quotes' => [
                [
                    'id' => 1,
                    'service_category' => 'Airport Transfer',
                    'service_type' => [
                        'id' => 1,
                        'name' => 'One Way',
                        'code' => 'OW',
                    ],
                    'vehicle_type' => [
                        'id' => 1,
                        'name' => 'Chevrolet Suburban',
                        'code' => 'SUV',
                        'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/luxury_j4wmyt.webp',
                        'max_pax' => 8,
                        'max_units' => 4,
                        'travel_time' => '30-45 minutes',
                        'features' => [
                            [
                                'id' => 1,
                                'name' => 'Meet & Greet',
                                'description' => 'Personal greeting at arrival',
                                'icon' => 'user-check'
                            ],
                            [
                                'id' => 2,
                                'name' => 'Flight Monitoring',
                                'description' => 'We track your flight status',
                                'icon' => 'plane'
                            ],
                            [
                                'id' => 3,
                                'name' => 'Free Cancellation',
                                'description' => 'Cancel up to 24 hours before',
                                'icon' => 'x-circle'
                            ]
                        ],
                    ],
                    'route' => [
                        'from' => [
                            'id' => 1,
                            'name' => 'Cancun Airport (CUN)',
                            'type' => 'A',
                            'city' => 'Cancun',
                            'city_id' => 1,
                        ],
                        'to' => [
                            'id' => 2,
                            'name' => 'Hotel Zone',
                            'type' => 'Z',
                            'city' => 'Cancun',
                            'city_id' => 1,
                        ],
                    ],
                    'pricing' => [
                        'cost_one_way' => number_format(45 * $multiplier, 2, '.', ''),
                        'total_one_way' => (int) round(45 * $multiplier),
                        'cost_round_trip' => null,
                        'total_round_trip' => null,
                        'starting_from' => (int) round(45 * $multiplier),
                    ],
                    'highlight' => [
                        'badge' => 'Standard',
                        'description' => 'Direct transportation from Cancun Airport to your destination',
                    ],
                    'num_vehicles' => 1,
                    'available' => 1,
                ],
                [
                    'id' => 2,
                    'service_category' => 'Hotel Transfer',
                    'service_type' => [
                        'id' => 1,
                        'name' => 'One Way',
                        'code' => 'OW',
                    ],
                    'vehicle_type' => [
                        'id' => 2,
                        'name' => 'Ford Transit',
                        'code' => 'VAN',
                        'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/crafter_c2mvxn.webp',
                        'max_pax' => 8,
                        'max_units' => 4,
                        'travel_time' => '15-30 minutes',
                        'features' => [
                            [
                                'id' => 4,
                                'name' => 'Door-to-door',
                                'description' => 'Pick up and drop off at your location',
                                'icon' => 'home'
                            ],
                            [
                                'id' => 5,
                                'name' => 'Professional Driver',
                                'description' => 'Licensed and experienced drivers',
                                'icon' => 'user'
                            ],
                            [
                                'id' => 6,
                                'name' => 'A/C Vehicles',
                                'description' => 'Air conditioned comfortable ride',
                                'icon' => 'wind'
                            ]
                        ],
                    ],
                    'route' => [
                        'from' => [
                            'id' => 3,
                            'name' => 'Hotel Riu Palace',
                            'type' => 'H',
                            'city' => 'Cancun',
                            'city_id' => 1,
                        ],
                        'to' => [
                            'id' => 4,
                            'name' => 'Downtown Cancun',
                            'type' => 'Z',
                            'city' => 'Cancun',
                            'city_id' => 1,
                        ],
                    ],
                    'pricing' => [
                        'cost_one_way' => number_format(35 * $multiplier, 2, '.', ''),
                        'total_one_way' => (int) round(35 * $multiplier),
                        'cost_round_trip' => null,
                        'total_round_trip' => null,
                        'starting_from' => (int) round(35 * $multiplier),
                    ],
                    'highlight' => [
                        'badge' => 'Standard',
                        'description' => 'Comfortable rides between hotels and popular destinations',
                    ],
                    'num_vehicles' => 1,
                    'available' => 1,
                ],
                [
                    'id' => 3,
                    'service_category' => 'Private Transfer',
                    'service_type' => [
                        'id' => 1,
                        'name' => 'One Way',
                        'code' => 'OW',
                    ],
                    'vehicle_type' => [
                        'id' => 3,
                        'name' => 'Cadillac Escalade',
                        'code' => 'LUX',
                        'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/economic_yjkomz.webp',
                        'max_pax' => 6,
                        'max_units' => 3,
                        'travel_time' => '30-45 minutes',
                        'features' => [
                            [
                                'id' => 7,
                                'name' => 'Private Vehicle',
                                'description' => 'Exclusive vehicle just for you and your group',
                                'icon' => 'star'
                            ],
                            [
                                'id' => 8,
                                'name' => 'Flexible Schedule',
                                'description' => 'Travel on your own schedule',
                                'icon' => 'clock'
                            ],
                            [
                                'id' => 9,
                                'name' => 'Premium Service',
                                'description' => 'Luxury experience with premium amenities',
                                'icon' => 'crown'
                            ]
                        ],
                    ],
                    'route' => [
                        'from' => [
                            'id' => 1,
                            'name' => 'Cancun Airport (CUN)',
                            'type' => 'A',
                            'city' => 'Cancun',
                            'city_id' => 1,
                        ],
                        'to' => [
                            'id' => 5,
                            'name' => 'Playa del Carmen',
                            'type' => 'Z',
                            'city' => 'Playa del Carmen',
                            'city_id' => 2,
                        ],
                    ],
                    'pricing' => [
                        'cost_one_way' => number_format(65 * $multiplier, 2, '.', ''),
                        'total_one_way' => (int) round(65 * $multiplier),
                        'cost_round_trip' => null,
                        'total_round_trip' => null,
                        'starting_from' => (int) round(65 * $multiplier),
                    ],
                    'highlight' => [
                        'badge' => 'Private',
                        'description' => 'Exclusive vehicle just for you and your group',
                    ],
                    'num_vehicles' => 1,
                    'available' => 1,
                ],
            ]
        ];

        return $this->resourceResponse('highlighted_quotes', 'retrieved', $response);
    }
}
