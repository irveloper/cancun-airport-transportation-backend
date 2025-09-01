<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\RateController;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\Location;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RateControllerTest extends TestCase
{
    use RefreshDatabase;

    private RateController $controller;
    private ServiceType $serviceType;
    private VehicleType $vehicleType;
    private Zone $fromZone;
    private Zone $toZone;
    private Location $fromLocation;
    private Location $toLocation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RateController();
        $this->createRateTestData();
    }

    protected function createRateTestData(): void
    {
        $city = City::create([
            'name' => 'Miami',
            'state' => 'FL',
            'country' => 'US'
        ]);

        $this->serviceType = ServiceType::create([
            'name' => 'Airport Transfer',
            'code' => 'airport',
            'tpv_type' => 'service_airport',
            'active' => true
        ]);

        $this->vehicleType = VehicleType::create([
            'name' => 'Sedan',
            'code' => 'sedan',
            'max_pax' => 4,
            'max_units' => 10,
            'active' => true
        ]);

        $this->fromZone = Zone::create([
            'name' => 'South Beach',
            'city_id' => $city->id,
            'active' => true
        ]);

        $this->toZone = Zone::create([
            'name' => 'Airport',
            'city_id' => $city->id,
            'active' => true
        ]);

        $this->fromLocation = Location::create([
            'name' => 'Hotel A',
            'address' => '123 Ocean Dr',
            'type' => 'hotel',
            'zone_id' => $this->fromZone->id,
            'latitude' => 25.7617,
            'longitude' => -80.1918,
            'active' => true
        ]);

        $this->toLocation = Location::create([
            'name' => 'Miami Airport',
            'address' => '2100 NW 42nd Ave',
            'type' => 'airport',
            'zone_id' => $this->toZone->id,
            'latitude' => 25.7932,
            'longitude' => -80.2906,
            'active' => true
        ]);
    }

    public function test_index_returns_paginated_rates(): void
    {
        // Create multiple rates
        for ($i = 0; $i < 25; $i++) {
            Rate::create([
                'service_type_id' => $this->serviceType->id,
                'vehicle_type_id' => $this->vehicleType->id,
                'from_zone_id' => $this->fromZone->id,
                'to_zone_id' => $this->toZone->id,
                'cost_vehicle_one_way' => 50.00 + $i,
                'total_one_way' => 60.00 + $i,
                'cost_vehicle_round_trip' => 90.00 + $i,
                'total_round_trip' => 110.00 + $i,
                'num_vehicles' => 1,
                'available' => true
            ]);
        }

        $request = new Request(['per_page' => 10]);
        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertCount(10, $data['data']['rates']);
        $this->assertEquals(25, $data['data']['pagination']['total']);
        $this->assertEquals(10, $data['data']['pagination']['per_page']);
        $this->assertEquals(3, $data['data']['pagination']['last_page']);
        $this->assertTrue($data['data']['pagination']['has_more_pages']);
    }

    public function test_index_with_filters(): void
    {
        // Create rates with different properties
        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 45.00,
            'total_one_way' => 55.00,
            'cost_vehicle_round_trip' => 85.00,
            'total_round_trip' => 105.00,
            'num_vehicles' => 1,
            'available' => false
        ]);

        // Test filtering by service type
        $request = new Request(['service_type_id' => $this->serviceType->id]);
        $response = $this->controller->index($request);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['data']['rates']);

        // Test filtering by availability
        $request = new Request(['available' => true]);
        $response = $this->controller->index($request);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['data']['rates']);
        $this->assertTrue($data['data']['rates'][0]['available']);

        // Test filtering by rate type
        $request = new Request(['rate_type' => 'zone']);
        $response = $this->controller->index($request);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['data']['rates']);
    }

    public function test_show_returns_specific_rate(): void
    {
        $rate = Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        $response = $this->controller->show($rate->id);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals($rate->id, $data['data']['rate']['id']);
        $this->assertEquals('Airport Transfer', $data['data']['rate']['service_type']['name']);
        $this->assertEquals('Sedan', $data['data']['rate']['vehicle_type']['name']);
        $this->assertEquals('South Beach', $data['data']['rate']['from_zone']['name']);
        $this->assertEquals('Airport', $data['data']['rate']['to_zone']['name']);
        $this->assertEquals('zone', $data['data']['rate']['pricing_type']);
    }

    public function test_show_with_nonexistent_rate_returns_404(): void
    {
        $response = $this->controller->show('nonexistent-id');

        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Rate not found', $data['message']);
    }

    public function test_store_creates_new_zone_based_rate(): void
    {
        $requestData = [
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ];

        $request = new Request($requestData);
        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Rate created successfully', $data['message']);
        $this->assertEquals(50.00, $data['data']['rate']['cost_vehicle_one_way']);
        $this->assertEquals('zone', $data['data']['rate']['pricing_type']);

        // Verify in database
        $this->assertDatabaseHas('rates', [
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00
        ]);
    }

    public function test_store_creates_location_specific_rate(): void
    {
        $requestData = [
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'from_location_id' => $this->fromLocation->id,
            'to_location_id' => $this->toLocation->id,
            'cost_vehicle_one_way' => 55.00,
            'total_one_way' => 65.00,
            'cost_vehicle_round_trip' => 100.00,
            'total_round_trip' => 120.00,
            'num_vehicles' => 1,
            'available' => true
        ];

        $request = new Request($requestData);
        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('location', $data['data']['rate']['pricing_type']);
        $this->assertEquals('Hotel A', $data['data']['rate']['from_location']['name']);
    }

    public function test_store_validates_location_belongs_to_zone(): void
    {
        // Create a location in different zone
        $otherZone = Zone::create([
            'name' => 'Other Zone',
            'city_id' => City::first()->id,
            'active' => true
        ]);

        $otherLocation = Location::create([
            'name' => 'Other Hotel',
            'address' => '456 Other St',
            'type' => 'hotel',
            'zone_id' => $otherZone->id,
            'latitude' => 25.7617,
            'longitude' => -80.1918,
            'active' => true
        ]);

        $requestData = [
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'from_location_id' => $otherLocation->id, // Wrong zone
            'to_location_id' => $this->toLocation->id,
            'cost_vehicle_one_way' => 55.00,
            'total_one_way' => 65.00,
            'cost_vehicle_round_trip' => 100.00,
            'total_round_trip' => 120.00,
            'num_vehicles' => 1,
            'available' => true
        ];

        $request = new Request($requestData);
        $response = $this->controller->store($request);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('From location must belong to the specified from zone', $data['message']);
    }

    public function test_store_validation_errors(): void
    {
        // Test missing required fields
        $request = new Request([]);
        $response = $this->controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());

        // Test invalid service type
        $request = new Request([
            'service_type_id' => 999,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1
        ]);
        $response = $this->controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());

        // Test negative prices
        $request = new Request([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => -10.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1
        ]);
        $response = $this->controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_update_modifies_existing_rate(): void
    {
        $rate = Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        $updateData = [
            'cost_vehicle_one_way' => 55.00,
            'total_one_way' => 65.00,
            'available' => false
        ];

        $request = new Request($updateData);
        $response = $this->controller->update($request, $rate->id);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Rate updated successfully', $data['message']);
        $this->assertEquals(55.00, $data['data']['rate']['cost_vehicle_one_way']);
        $this->assertEquals(65.00, $data['data']['rate']['total_one_way']);
        $this->assertFalse($data['data']['rate']['available']);

        // Verify in database
        $rate->refresh();
        $this->assertEquals(55.00, $rate->cost_vehicle_one_way);
        $this->assertEquals(65.00, $rate->total_one_way);
        $this->assertFalse($rate->available);
    }

    public function test_update_with_nonexistent_rate_returns_404(): void
    {
        $request = new Request(['cost_vehicle_one_way' => 55.00]);
        $response = $this->controller->update($request, 'nonexistent-id');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_destroy_deletes_rate(): void
    {
        $rate = Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        $response = $this->controller->destroy($rate->id);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Rate deleted successfully', $data['message']);

        // Verify deleted from database
        $this->assertDatabaseMissing('rates', ['id' => $rate->id]);
    }

    public function test_destroy_with_nonexistent_rate_returns_404(): void
    {
        $response = $this->controller->destroy('nonexistent-id');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_get_route_rates(): void
    {
        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        $request = new Request([
            'service_type_id' => $this->serviceType->id,
            'from_location_id' => $this->fromLocation->id,
            'to_location_id' => $this->toLocation->id
        ]);

        $response = $this->controller->getRouteRates($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Route rates retrieved successfully', $data['message']);
        $this->assertNotEmpty($data['data']['rates']);
    }

    public function test_get_zone_rates(): void
    {
        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ]);

        $request = new Request([
            'service_type_id' => $this->serviceType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id
        ]);

        $response = $this->controller->getZoneRates($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Zone rates retrieved successfully', $data['message']);
        $this->assertNotEmpty($data['data']['rates']);
    }

    public function test_get_route_rates_validation_errors(): void
    {
        $request = new Request([]);
        $response = $this->controller->getRouteRates($request);
        $this->assertEquals(422, $response->getStatusCode());

        $request = new Request([
            'service_type_id' => 999,
            'from_location_id' => $this->fromLocation->id,
            'to_location_id' => $this->toLocation->id
        ]);
        $response = $this->controller->getRouteRates($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_get_zone_rates_validation_errors(): void
    {
        $request = new Request([]);
        $response = $this->controller->getZoneRates($request);
        $this->assertEquals(422, $response->getStatusCode());

        $request = new Request([
            'service_type_id' => $this->serviceType->id,
            'from_zone_id' => 999,
            'to_zone_id' => $this->toZone->id
        ]);
        $response = $this->controller->getZoneRates($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_format_rate_response_structure(): void
    {
        $rate = Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'from_location_id' => $this->fromLocation->id,
            'to_location_id' => $this->toLocation->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true,
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth()
        ]);

        $response = $this->controller->show($rate->id);
        $data = json_decode($response->getContent(), true);

        $rateData = $data['data']['rate'];

        // Check required fields
        $this->assertArrayHasKey('id', $rateData);
        $this->assertArrayHasKey('service_type', $rateData);
        $this->assertArrayHasKey('vehicle_type', $rateData);
        $this->assertArrayHasKey('cost_vehicle_one_way', $rateData);
        $this->assertArrayHasKey('total_one_way', $rateData);
        $this->assertArrayHasKey('cost_vehicle_round_trip', $rateData);
        $this->assertArrayHasKey('total_round_trip', $rateData);
        $this->assertArrayHasKey('num_vehicles', $rateData);
        $this->assertArrayHasKey('available', $rateData);
        $this->assertArrayHasKey('valid_from', $rateData);
        $this->assertArrayHasKey('valid_to', $rateData);
        $this->assertArrayHasKey('created_at', $rateData);
        $this->assertArrayHasKey('updated_at', $rateData);

        // Check zone information
        $this->assertArrayHasKey('from_zone', $rateData);
        $this->assertArrayHasKey('to_zone', $rateData);

        // Check location information (since this rate has locations)
        $this->assertArrayHasKey('from_location', $rateData);
        $this->assertArrayHasKey('to_location', $rateData);
        $this->assertEquals('location', $rateData['pricing_type']);

        // Check nested structure
        $this->assertArrayHasKey('id', $rateData['service_type']);
        $this->assertArrayHasKey('name', $rateData['service_type']);
        $this->assertArrayHasKey('code', $rateData['service_type']);
    }

    public function test_date_filtering(): void
    {
        $futureDate = Carbon::now()->addWeek();

        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true,
            'valid_from' => $futureDate,
            'valid_to' => $futureDate->copy()->addMonth()
        ]);

        $request = new Request([
            'valid_date' => $futureDate->format('Y-m-d')
        ]);

        $response = $this->controller->index($request);
        $data = json_decode($response->getContent(), true);

        $this->assertNotEmpty($data['data']['rates']);

        // Test with past date - should not find the rate
        $request = new Request([
            'valid_date' => Carbon::now()->subDay()->format('Y-m-d')
        ]);

        $response = $this->controller->index($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEmpty($data['data']['rates']);
    }
}