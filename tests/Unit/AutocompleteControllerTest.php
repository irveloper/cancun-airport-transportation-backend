<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\AutocompleteController;
use App\Models\Location;
use App\Models\Zone;
use App\Models\City;
use App\Models\Airport;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

class AutocompleteControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AutocompleteController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AutocompleteController();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create cities
        $miami = City::create([
            'name' => 'Miami',
            'state' => 'FL',
            'country' => 'US'
        ]);
        
        $fortLauderdale = City::create([
            'name' => 'Fort Lauderdale',
            'state' => 'FL',
            'country' => 'US'
        ]);

        // Create zones
        $southBeach = Zone::create([
            'name' => 'South Beach',
            'city_id' => $miami->id,
            'active' => true
        ]);
        
        $downtown = Zone::create([
            'name' => 'Downtown Miami',
            'city_id' => $miami->id,
            'active' => true
        ]);

        $airportZone = Zone::create([
            'name' => 'Airport Zone',
            'city_id' => $fortLauderdale->id,
            'active' => true
        ]);

        // Create locations
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
            'name' => 'Miami Marriott Biscayne Bay',
            'address' => '1633 N Bayshore Dr, Miami',
            'type' => 'hotel',
            'zone_id' => $downtown->id,
            'latitude' => 25.7933,
            'longitude' => -80.1867,
            'active' => true
        ]);

        Location::create([
            'name' => 'Inactive Hotel',
            'address' => '123 Test Ave, Miami',
            'type' => 'hotel',
            'zone_id' => $southBeach->id,
            'latitude' => 25.7617,
            'longitude' => -80.1918,
            'active' => false
        ]);

        // Create airports
        Airport::create([
            'name' => 'Miami International Airport',
            'code' => 'MIA',
            'city_id' => $miami->id
        ]);

        Airport::create([
            'name' => 'Fort Lauderdale-Hollywood International Airport',
            'code' => 'FLL',
            'city_id' => $fortLauderdale->id
        ]);
    }

    public function test_search_with_empty_query_returns_empty_results(): void
    {
        $request = new Request(['q' => '']);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Please provide a search query', $data['message']);
        $this->assertEmpty($data['data']['airport']);
        $this->assertEmpty($data['data']['zones']);
        $this->assertEmpty($data['data']['locations']);
        $this->assertEquals(0, $data['data']['meta']['total_results']);
        $this->assertEquals('none', $data['data']['meta']['search_context']);
    }

    public function test_search_with_no_query_parameter_returns_empty_results(): void
    {
        $request = new Request([]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Please provide a search query', $data['message']);
        $this->assertEmpty($data['data']['airport']);
        $this->assertEmpty($data['data']['zones']);
        $this->assertEmpty($data['data']['locations']);
    }

    public function test_search_with_query_parameter_instead_of_q(): void
    {
        $request = new Request(['query' => 'Miami']);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Miami', $data['data']['meta']['query']);
    }

    public function test_search_defaults_to_destinations_context(): void
    {
        $request = new Request(['q' => 'Miami']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('destinations', $data['data']['meta']['search_context']);
        $this->assertEmpty($data['data']['airport']);
        $this->assertNotEmpty($data['data']['zones']);
        $this->assertNotEmpty($data['data']['locations']);
    }

    public function test_search_shows_airports_for_departure_from(): void
    {
        $request = new Request([
            'q' => 'Miami',
            'type' => 'departure',
            'input' => 'from'
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('airports', $data['data']['meta']['search_context']);
        $this->assertNotEmpty($data['data']['airport']);
        $this->assertEmpty($data['data']['zones']);
        $this->assertEmpty($data['data']['locations']);
    }

    public function test_search_shows_airports_for_arrival_to(): void
    {
        $request = new Request([
            'q' => 'Fort',
            'type' => 'arrival',
            'input' => 'to'
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('airports', $data['data']['meta']['search_context']);
        $this->assertNotEmpty($data['data']['airport']);
        
        // Check that airport data is properly formatted
        $airport = $data['data']['airport'][0];
        $this->assertArrayHasKey('id', $airport);
        $this->assertArrayHasKey('name', $airport);
        $this->assertArrayHasKey('code', $airport);
        $this->assertArrayHasKey('city', $airport);
        $this->assertEquals('FLL', $airport['code']);
    }

    public function test_search_shows_airports_for_round_trip_from(): void
    {
        $request = new Request([
            'q' => 'Miami',
            'type' => 'round-trip',
            'input' => 'from'
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('airports', $data['data']['meta']['search_context']);
        $this->assertNotEmpty($data['data']['airport']);
    }

    public function test_search_location_by_name(): void
    {
        $request = new Request(['q' => 'Fontainebleau']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']['locations']);
        
        $found = false;
        foreach ($data['data']['locations'] as $cityGroup) {
            foreach ($cityGroup['locations'] as $location) {
                if (strpos($location['name'], 'Fontainebleau') !== false) {
                    $found = true;
                    $this->assertArrayHasKey('id', $location);
                    $this->assertArrayHasKey('name', $location);
                    $this->assertArrayHasKey('type', $location);
                    $this->assertArrayHasKey('city', $location);
                    $this->assertArrayHasKey('zone', $location);
                    break 2;
                }
            }
        }
        
        $this->assertTrue($found, 'Fontainebleau location should be found');
    }

    public function test_search_location_by_address(): void
    {
        $request = new Request(['q' => 'Collins']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['locations']);
        
        $found = false;
        foreach ($data['data']['locations'] as $cityGroup) {
            foreach ($cityGroup['locations'] as $location) {
                if (strpos($location['name'], 'Fontainebleau') !== false) {
                    $found = true;
                    break 2;
                }
            }
        }
        
        $this->assertTrue($found, 'Location should be found by address');
    }

    public function test_search_zones(): void
    {
        $request = new Request(['q' => 'South']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['zones']);
        
        $zone = $data['data']['zones'][0];
        $this->assertArrayHasKey('id', $zone);
        $this->assertArrayHasKey('name', $zone);
        $this->assertArrayHasKey('city', $zone);
        $this->assertEquals('South Beach', $zone['name']);
    }

    public function test_search_airport_by_code(): void
    {
        $request = new Request([
            'q' => 'MIA',
            'type' => 'departure',
            'input' => 'from'
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['airport']);
        
        $airport = $data['data']['airport'][0];
        $this->assertEquals('MIA', $airport['code']);
        $this->assertEquals('Miami International Airport', $airport['name']);
    }

    public function test_search_excludes_inactive_locations(): void
    {
        $request = new Request(['q' => 'Inactive']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        // Should not find any results for inactive locations
        $this->assertEmpty($data['data']['locations']);
    }

    public function test_search_respects_limit_parameter(): void
    {
        $request = new Request([
            'q' => 'Miami',
            'limit' => 1
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        // Count total locations returned
        $totalLocations = 0;
        foreach ($data['data']['locations'] as $cityGroup) {
            $totalLocations += count($cityGroup['locations']);
        }
        
        // With limit 1, should have at most 1 location
        $this->assertLessThanOrEqual(1, $totalLocations);
        
        // Zones should also respect the limit (min of limit and 10)
        $this->assertLessThanOrEqual(1, count($data['data']['zones']));
    }

    public function test_validation_errors(): void
    {
        // Test invalid language
        $request = new Request([
            'q' => 'Miami',
            'lang' => 'invalid'
        ]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        // Test invalid type
        $request = new Request([
            'q' => 'Miami',
            'type' => 'invalid'
        ]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        // Test invalid input
        $request = new Request([
            'q' => 'Miami',
            'input' => 'invalid'
        ]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        // Test query too long
        $request = new Request([
            'q' => str_repeat('a', 256)
        ]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        // Test limit too high
        $request = new Request([
            'q' => 'Miami',
            'limit' => 101
        ]);
        
        $response = $this->controller->search($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_search_meta_information(): void
    {
        $request = new Request([
            'q' => 'Miami',
            'lang' => 'es',
            'type' => 'hotel-to-hotel',
            'input' => 'to',
            'limit' => 5
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $meta = $data['data']['meta'];
        $this->assertEquals('Miami', $meta['query']);
        $this->assertEquals('es', $meta['lang']);
        $this->assertEquals('hotel-to-hotel', $meta['type']);
        $this->assertEquals('to', $meta['input']);
        $this->assertEquals('destinations', $meta['search_context']);
        $this->assertArrayHasKey('total_results', $meta);
    }

    public function test_case_insensitive_search(): void
    {
        $request = new Request(['q' => 'miami']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['zones']);
        $this->assertNotEmpty($data['data']['locations']);
        
        // Should find Miami-related results even with lowercase query
        $found = false;
        foreach ($data['data']['zones'] as $zone) {
            if (stripos($zone['name'], 'Miami') !== false || stripos($zone['city'], 'Miami') !== false) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find Miami results with case-insensitive search');
    }

    public function test_grouped_locations_structure(): void
    {
        $request = new Request(['q' => 'Miami']);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['locations']);
        
        // Check structure of grouped locations
        foreach ($data['data']['locations'] as $cityId => $cityGroup) {
            $this->assertArrayHasKey('name', $cityGroup);
            $this->assertArrayHasKey('locations', $cityGroup);
            $this->assertIsArray($cityGroup['locations']);
            
            foreach ($cityGroup['locations'] as $location) {
                $this->assertArrayHasKey('id', $location);
                $this->assertArrayHasKey('name', $location);
                $this->assertArrayHasKey('type', $location);
                $this->assertArrayHasKey('city', $location);
                $this->assertArrayHasKey('zone', $location);
                $this->assertArrayHasKey('id', $location['zone']);
                $this->assertArrayHasKey('name', $location['zone']);
            }
        }
    }

    public function test_airport_search_priority_by_code(): void
    {
        $request = new Request([
            'q' => 'MIA Miami',
            'type' => 'departure',
            'input' => 'from'
        ]);
        
        $response = $this->controller->search($request);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertNotEmpty($data['data']['airport']);
        
        // Airport with exact code match should appear first
        $firstAirport = $data['data']['airport'][0];
        $this->assertEquals('MIA', $firstAirport['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}