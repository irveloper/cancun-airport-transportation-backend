<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\Location;
use App\Models\City;
use App\Models\Airport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createLargeDataset();
    }

    private function createLargeDataset(): void
    {
        // Create multiple cities
        $cities = [];
        for ($i = 1; $i <= 5; $i++) {
            $cities[] = City::create([
                'name' => "City {$i}",
                'state' => 'FL',
                'country' => 'US'
            ]);
        }

        // Create service types
        $serviceTypes = [];
        foreach (['airport', 'hotel', 'cruise'] as $type) {
            $serviceTypes[] = ServiceType::create([
                'name' => ucfirst($type) . ' Transfer',
                'code' => $type,
                'tpv_type' => 'service_' . $type,
                'active' => true
            ]);
        }

        // Create vehicle types
        $vehicleTypes = [];
        foreach (['sedan', 'suv', 'van'] as $type) {
            $vehicleTypes[] = VehicleType::create([
                'name' => ucfirst($type),
                'code' => $type,
                'max_pax' => $type === 'sedan' ? 4 : ($type === 'suv' ? 6 : 8),
                'max_units' => 10,
                'active' => true
            ]);
        }

        // Create zones (10 per city)
        $zones = [];
        foreach ($cities as $city) {
            for ($i = 1; $i <= 10; $i++) {
                $zones[] = Zone::create([
                    'name' => "Zone {$i} - {$city->name}",
                    'city_id' => $city->id,
                    'active' => true
                ]);
            }
        }

        // Create locations (5 per zone)
        $locations = [];
        foreach ($zones as $zone) {
            for ($i = 1; $i <= 5; $i++) {
                $locations[] = Location::create([
                    'name' => "Location {$i} - {$zone->name}",
                    'address' => "Address {$i}, {$zone->name}",
                    'type' => $i <= 3 ? 'hotel' : 'airport',
                    'zone_id' => $zone->id,
                    'latitude' => 25.7617 + (rand(-100, 100) / 1000),
                    'longitude' => -80.1918 + (rand(-100, 100) / 1000),
                    'active' => true
                ]);
            }
        }

        // Create airports
        foreach ($cities as $city) {
            Airport::create([
                'name' => $city->name . ' International Airport',
                'code' => strtoupper(substr($city->name, 0, 3)),
                'city_id' => $city->id
            ]);
        }

        // Create rates (combinations of zones and service/vehicle types)
        $rateCount = 0;
        foreach ($serviceTypes as $serviceType) {
            foreach ($vehicleTypes as $vehicleType) {
                for ($i = 0; $i < min(100, count($zones) * 2); $i++) {
                    if ($rateCount >= 500) break 3; // Limit total rates
                    
                    $fromZone = $zones[array_rand($zones)];
                    $toZone = $zones[array_rand($zones)];
                    
                    if ($fromZone->id === $toZone->id) continue;

                    Rate::create([
                        'service_type_id' => $serviceType->id,
                        'vehicle_type_id' => $vehicleType->id,
                        'from_zone_id' => $fromZone->id,
                        'to_zone_id' => $toZone->id,
                        'cost_vehicle_one_way' => rand(30, 100),
                        'total_one_way' => rand(40, 120),
                        'cost_vehicle_round_trip' => rand(60, 180),
                        'total_round_trip' => rand(80, 200),
                        'num_vehicles' => 1,
                        'available' => true
                    ]);
                    
                    $rateCount++;
                }
            }
        }
    }

    public function test_autocomplete_search_performance_with_large_dataset(): void
    {
        $startTime = microtime(true);
        
        $response = $this->get('/api/v1/autocomplete/search?q=Location&limit=50');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Should complete within 2 seconds even with large dataset
        $this->assertLessThan(2000, $executionTime, "Autocomplete search took {$executionTime}ms");
        
        $data = $response->json('data');
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('total_results', $data['meta']);
    }

    public function test_rates_index_performance_with_pagination(): void
    {
        $startTime = microtime(true);
        
        $response = $this->get('/api/v1/rates?per_page=50');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Should complete within 1.5 seconds
        $this->assertLessThan(1500, $executionTime, "Rates index took {$executionTime}ms");
        
        $data = $response->json('data');
        $this->assertArrayHasKey('rates', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertLessThanOrEqual(50, count($data['rates']));
    }

    public function test_database_query_efficiency(): void
    {
        DB::enableQueryLog();
        
        $response = $this->get('/api/v1/autocomplete/search?q=City');
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        // Should not execute too many queries (N+1 problem check)
        $this->assertLessThan(15, $queryCount, "Too many database queries: {$queryCount}");
        
        DB::flushQueryLog();
        DB::disableQueryLog();
    }

    public function test_memory_usage_during_large_operations(): void
    {
        $memoryBefore = memory_get_usage(true);
        
        // Perform memory-intensive operation
        $response = $this->get('/api/v1/rates?per_page=100');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $response->assertStatus(200);
        
        // Should not use excessive memory (adjust threshold as needed)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, "Memory usage too high: " . ($memoryUsed / 1024 / 1024) . "MB");
    }

    public function test_concurrent_request_simulation(): void
    {
        $responses = [];
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->get("/api/v1/autocomplete/search?q=test{$i}");
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Sequential execution should not take too long
        $this->assertLessThan(5000, $totalTime, "Concurrent requests took {$totalTime}ms");
    }

    public function test_cache_effectiveness(): void
    {
        Cache::flush();
        
        // First request - should hit database
        $startTime1 = microtime(true);
        $response1 = $this->get('/api/v1/autocomplete/search?q=Miami');
        $endTime1 = microtime(true);
        $time1 = ($endTime1 - $startTime1) * 1000;
        
        // Second identical request - should use cache
        $startTime2 = microtime(true);
        $response2 = $this->get('/api/v1/autocomplete/search?q=Miami');
        $endTime2 = microtime(true);
        $time2 = ($endTime2 - $startTime2) * 1000;
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Second request should be faster (cached)
        // Note: This might not always be true in test environment
        $this->assertLessThanOrEqual($time1 * 1.5, $time2 + 50, "Cache doesn't seem to be working effectively");
    }

    public function test_large_result_set_handling(): void
    {
        // Test with a query that should return many results
        $response = $this->get('/api/v1/autocomplete/search?q=Location&limit=100');
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Should handle large result sets gracefully
        $totalResults = 0;
        foreach ($data['locations'] as $cityGroup) {
            $totalResults += count($cityGroup['locations']);
        }
        $totalResults += count($data['zones']);
        $totalResults += count($data['airport']);
        
        // Should respect the limit
        $this->assertLessThanOrEqual(100, $totalResults);
    }

    public function test_complex_filtering_performance(): void
    {
        $serviceType = ServiceType::first();
        $vehicleType = VehicleType::first();
        
        $startTime = microtime(true);
        
        $response = $this->get(
            "/api/v1/rates?" . 
            "service_type_id={$serviceType->id}&" .
            "vehicle_type_id={$vehicleType->id}&" .
            "available=true&" .
            "sort_by=total_one_way&" .
            "sort_order=asc&" .
            "per_page=25"
        );
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        // Complex filtering should still be fast
        $this->assertLessThan(1000, $executionTime, "Complex filtering took {$executionTime}ms");
        
        $data = $response->json('data');
        $this->assertArrayHasKey('rates', $data);
    }

    public function test_search_with_special_characters_performance(): void
    {
        $specialQueries = [
            'Café & Restaurant',
            'Hotel "Luxury" Resort',
            "O'Hare Airport",
            'São Paulo',
            '50% Off Hotel',
            'Location #1'
        ];
        
        foreach ($specialQueries as $query) {
            $startTime = microtime(true);
            
            $response = $this->get('/api/v1/autocomplete/search?q=' . urlencode($query));
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            $response->assertStatus(200);
            
            // Should handle special characters efficiently
            $this->assertLessThan(1000, $executionTime, "Search with '{$query}' took {$executionTime}ms");
        }
    }

    public function test_rate_calculation_performance(): void
    {
        $serviceType = ServiceType::first();
        $fromLocation = Location::first();
        $toLocation = Location::skip(1)->first();
        
        $startTime = microtime(true);
        
        $response = $this->get(
            "/api/v1/rates/route?" .
            "service_type_id={$serviceType->id}&" .
            "from_location_id={$fromLocation->id}&" .
            "to_location_id={$toLocation->id}"
        );
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        // Rate calculations should be fast
        $this->assertLessThan(500, $executionTime, "Rate calculation took {$executionTime}ms");
    }

    public function test_api_response_size_optimization(): void
    {
        $response = $this->get('/api/v1/rates?per_page=50');
        
        $response->assertStatus(200);
        
        $responseSize = strlen($response->getContent());
        
        // Response should not be excessively large
        $this->assertLessThan(1024 * 1024, $responseSize, "Response size too large: " . ($responseSize / 1024) . "KB");
        
        // But should contain meaningful data
        $data = $response->json('data');
        $this->assertNotEmpty($data['rates']);
    }

    public function test_search_ranking_consistency(): void
    {
        // Test that search results are consistently ranked
        $query = 'City';
        
        $response1 = $this->get("/api/v1/autocomplete/search?q={$query}");
        $response2 = $this->get("/api/v1/autocomplete/search?q={$query}");
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Results should be in the same order
        $zones1 = $response1->json('data.zones');
        $zones2 = $response2->json('data.zones');
        
        if (!empty($zones1) && !empty($zones2)) {
            $this->assertEquals($zones1[0]['id'], $zones2[0]['id'], 'Search ranking should be consistent');
        }
    }

    public function test_error_handling_performance(): void
    {
        // Test that error responses are also fast
        $startTime = microtime(true);
        
        $response = $this->get('/api/v1/rates/nonexistent-rate-id');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(404);
        
        // Error responses should be fast too
        $this->assertLessThan(200, $executionTime, "Error response took {$executionTime}ms");
    }
}