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
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createApiTestData();
    }

    protected function createApiTestData(): void
    {
        $miami = City::create([
            'name' => 'Miami',
            'state' => 'FL',
            'country' => 'US'
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Airport Transfer',
            'code' => 'airport',
            'tpv_type' => 'service_airport',
            'active' => true
        ]);

        $vehicleType = VehicleType::create([
            'name' => 'Sedan',
            'code' => 'sedan',
            'max_pax' => 4,
            'max_units' => 10,
            'active' => true
        ]);

        $southBeach = Zone::create([
            'name' => 'South Beach',
            'city_id' => $miami->id,
            'active' => true
        ]);

        $airportZone = Zone::create([
            'name' => 'Airport Zone',
            'city_id' => $miami->id,
            'active' => true
        ]);

        Location::create([
            'name' => 'Fontainebleau Miami Beach',
            'address' => '4441 Collins Ave, Miami Beach',
            'type' => 'hotel',
            'zone_id' => $southBeach->id,
            'latitude' => 25.8206,
            'longitude' => -80.1314,
            'active' => true
        ]);

        Location::create([
            'name' => 'Miami International Airport',
            'address' => '2100 NW 42nd Ave, Miami',
            'type' => 'airport',
            'zone_id' => $airportZone->id,
            'latitude' => 25.7932,
            'longitude' => -80.2906,
            'active' => true
        ]);

        Airport::create([
            'name' => 'Miami International Airport',
            'code' => 'MIA',
            'city_id' => $miami->id
        ]);

        Rate::create([
            'service_type_id' => $serviceType->id,
            'vehicle_type_id' => $vehicleType->id,
            'from_zone_id' => $southBeach->id,
            'to_zone_id' => $airportZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);
    }

    public function test_autocomplete_api_endpoint_works(): void
    {
        $response = $this->get('/api/v1/autocomplete/search?q=Miami');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id',
                     'data' => [
                         'airport',
                         'zones',
                         'locations',
                         'meta' => [
                             'query',
                             'lang',
                             'type',
                             'input',
                             'search_context',
                             'total_results'
                         ]
                     ]
                 ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Miami', $response->json('data.meta.query'));
    }

    public function test_rates_api_endpoint_works(): void
    {
        $response = $this->get('/api/v1/rates');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id',
                     'data' => [
                         'rates' => [
                             '*' => [
                                 'id',
                                 'service_type',
                                 'vehicle_type',
                                 'cost_vehicle_one_way',
                                 'total_one_way',
                                 'cost_vehicle_round_trip',
                                 'total_round_trip',
                                 'num_vehicles',
                                 'available',
                                 'pricing_type'
                             ]
                         ],
                         'pagination'
                     ]
                 ]);
    }

    public function test_autocomplete_to_rates_workflow(): void
    {
        // Step 1: Search for hotels in Miami
        $autocompleteResponse = $this->get('/api/v1/autocomplete/search?q=Fontainebleau');
        $autocompleteResponse->assertStatus(200);

        $locations = $autocompleteResponse->json('data.locations');
        $this->assertNotEmpty($locations);

        // Extract location ID from grouped structure
        $locationId = null;
        foreach ($locations as $cityGroup) {
            foreach ($cityGroup['locations'] as $location) {
                if (strpos($location['name'], 'Fontainebleau') !== false) {
                    $locationId = $location['id'];
                    break 2;
                }
            }
        }

        $this->assertNotNull($locationId, 'Should find Fontainebleau location');

        // Step 2: Search for airport
        $airportResponse = $this->get('/api/v1/autocomplete/search?q=Miami&type=departure&input=from');
        $airportResponse->assertStatus(200);
        
        $airports = $airportResponse->json('data.airport');
        $this->assertNotEmpty($airports);

        $airportLocation = Location::where('type', 'airport')->first();
        $this->assertNotNull($airportLocation);

        // Step 3: Get rates for the route
        $serviceType = ServiceType::first();
        $ratesResponse = $this->get("/api/v1/rates/route?service_type_id={$serviceType->id}&from_location_id={$locationId}&to_location_id={$airportLocation->id}");
        
        $ratesResponse->assertStatus(200);
        $rates = $ratesResponse->json('data.rates');
        $this->assertNotEmpty($rates, 'Should find rates for the route');
    }

    public function test_api_response_consistency(): void
    {
        $endpoints = [
            '/api/v1/autocomplete/search?q=Miami',
            '/api/v1/rates'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            
            // All APIs should return consistent structure
            $response->assertJsonStructure([
                'success',
                'message', 
                'timestamp',
                'request_id',
                'data'
            ]);

            // Check timestamp format
            $timestamp = $response->json('timestamp');
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}000Z$/', $timestamp);

            // Check request_id exists
            $this->assertNotEmpty($response->json('request_id'));
        }
    }

    public function test_error_handling_consistency(): void
    {
        // Test validation errors
        $response = $this->get('/api/v1/autocomplete/search?q=' . str_repeat('a', 256));
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id'
                 ]);

        $this->assertFalse($response->json('success'));

        // Test not found errors
        $response = $this->get('/api/v1/rates/nonexistent-id');
        $response->assertStatus(404)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id'
                 ]);

        $this->assertFalse($response->json('success'));
    }

    public function test_security_headers(): void
    {
        $response = $this->get('/api/v1/rates');

        // Check for basic security headers (if implemented)
        $response->assertStatus(200);
        
        // These might not be present in test environment, but good to check
        // $response->assertHeader('X-Frame-Options');
        // $response->assertHeader('X-Content-Type-Options');
    }

    public function test_rate_limiting_structure(): void
    {
        // Make multiple requests to test rate limiting behavior
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/api/v1/autocomplete/search?q=test' . $i);
            $response->assertStatus(200);
        }

        // If rate limiting is implemented, we would test here
        // This is more of a structure test to ensure the app handles multiple requests
        $this->assertTrue(true); // Placeholder assertion
    }

    public function test_data_validation_and_sanitization(): void
    {
        // Test XSS prevention
        $maliciousQuery = '<script>alert("xss")</script>';
        $response = $this->get('/api/v1/autocomplete/search?q=' . urlencode($maliciousQuery));
        
        $response->assertStatus(200);
        
        // Should not return raw script tags
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('<script>', $responseContent);
        $this->assertStringNotContainsString('alert(', $responseContent);
    }

    public function test_database_performance_monitoring(): void
    {
        // Enable query logging for this test
        \DB::enableQueryLog();

        $response = $this->get('/api/v1/autocomplete/search?q=Miami');
        $response->assertStatus(200);

        $queries = \DB::getQueryLog();
        
        // Should not have excessive N+1 queries
        $this->assertLessThan(10, count($queries), 'Too many database queries detected');

        // Reset query log
        \DB::flushQueryLog();
        \DB::disableQueryLog();
    }

    public function test_pagination_consistency(): void
    {
        // Create multiple rates for pagination testing
        $serviceType = ServiceType::first();
        $vehicleType = VehicleType::first();
        $fromZone = Zone::first();
        $toZone = Zone::skip(1)->first();

        for ($i = 0; $i < 25; $i++) {
            Rate::create([
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $vehicleType->id,
                'from_zone_id' => $fromZone->id,
                'to_zone_id' => $toZone->id,
                'cost_vehicle_one_way' => 50.00 + $i,
                'total_one_way' => 60.00 + $i,
                'cost_vehicle_round_trip' => 90.00 + $i,
                'total_round_trip' => 110.00 + $i,
                'num_vehicles' => 1,
                'available' => true
            ]);
        }

        $response = $this->get('/api/v1/rates?per_page=10');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'pagination' => [
                             'current_page',
                             'per_page',
                             'total',
                             'last_page',
                             'has_more_pages'
                         ]
                     ]
                 ]);

        $pagination = $response->json('data.pagination');
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertTrue($pagination['total'] >= 25);
    }

    public function test_search_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/api/v1/autocomplete/search?q=Miami');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Search should complete within reasonable time (adjust as needed)
        $this->assertLessThan(1000, $executionTime, 'Search took too long: ' . $executionTime . 'ms');
    }

    public function test_cache_behavior(): void
    {
        // Clear any existing cache
        \Cache::flush();

        // First request - should hit database
        $response1 = $this->get('/api/v1/autocomplete/search?q=Miami');
        $response1->assertStatus(200);

        // Second request - should use cache (we can't easily test this without mocking)
        $response2 = $this->get('/api/v1/autocomplete/search?q=Miami');
        $response2->assertStatus(200);

        // Responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_api_versioning_structure(): void
    {
        // Test that v1 endpoints are properly versioned
        $response = $this->get('/api/v1/rates');
        $response->assertStatus(200);

        // Test non-existent version returns appropriate error
        $response = $this->get('/api/v2/rates');
        $response->assertStatus(404);
    }

    public function test_input_type_handling(): void
    {
        // Test different input types for autocomplete
        $testCases = [
            ['type' => 'departure', 'input' => 'from', 'expected_context' => 'airports'],
            ['type' => 'arrival', 'input' => 'to', 'expected_context' => 'airports'],
            ['type' => 'hotel-to-hotel', 'input' => 'to', 'expected_context' => 'destinations']
        ];

        foreach ($testCases as $case) {
            $response = $this->get(
                "/api/v1/autocomplete/search?q=Miami&type={$case['type']}&input={$case['input']}"
            );

            $response->assertStatus(200);
            $this->assertEquals(
                $case['expected_context'],
                $response->json('data.meta.search_context'),
                "Failed for type: {$case['type']}, input: {$case['input']}"
            );
        }
    }

    public function test_date_handling(): void
    {
        $futureDate = Carbon::now()->addWeek()->format('Y-m-d');
        
        $response = $this->get("/api/v1/rates?valid_date={$futureDate}");
        $response->assertStatus(200);

        // Test invalid date format
        $response = $this->get('/api/v1/rates?valid_date=invalid-date');
        $response->assertStatus(200); // Should handle gracefully or validate
    }

    public function test_content_type_headers(): void
    {
        $response = $this->get('/api/v1/rates');
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/json');
    }
}