<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\Location;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class RateModelTest extends TestCase
{
    use RefreshDatabase;

    private ServiceType $serviceType;
    private VehicleType $vehicleType;
    private Zone $fromZone;
    private Zone $toZone;
    private Location $fromLocation;
    private Location $toLocation;

    protected function setUp(): void
    {
        parent::setUp();
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
            'active' => true
        ]);

        $this->vehicleType = VehicleType::create([
            'name' => 'Sedan',
            'code' => 'sedan',
            'capacity' => 4,
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

    public function test_can_create_zone_based_rate(): void
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

        $this->assertInstanceOf(Rate::class, $rate);
        $this->assertTrue($rate->isZoneBased());
        $this->assertFalse($rate->isLocationSpecific());
        $this->assertEquals(50.00, $rate->cost_vehicle_one_way);
        $this->assertEquals(60.00, $rate->total_one_way);
        $this->assertEquals(90.00, $rate->cost_vehicle_round_trip);
        $this->assertEquals(110.00, $rate->total_round_trip);
    }

    public function test_can_create_location_specific_rate(): void
    {
        $rate = Rate::create([
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
        ]);

        $this->assertTrue($rate->isLocationSpecific());
        $this->assertTrue($rate->isZoneBased());
    }

    public function test_can_create_rate_with_date_validity(): void
    {
        $validFrom = Carbon::now()->addDay();
        $validTo = Carbon::now()->addWeek();

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
            'available' => true,
            'valid_from' => $validFrom,
            'valid_to' => $validTo
        ]);

        $this->assertEquals($validFrom->format('Y-m-d'), $rate->valid_from->format('Y-m-d'));
        $this->assertEquals($validTo->format('Y-m-d'), $rate->valid_to->format('Y-m-d'));
    }

    public function test_relationships_work_correctly(): void
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
            'available' => true
        ]);

        $this->assertEquals('Airport Transfer', $rate->serviceType->name);
        $this->assertEquals('Sedan', $rate->vehicleType->name);
        $this->assertEquals('South Beach', $rate->fromZone->name);
        $this->assertEquals('Airport', $rate->toZone->name);
        $this->assertEquals('Hotel A', $rate->fromLocation->name);
        $this->assertEquals('Miami Airport', $rate->toLocation->name);
    }

    public function test_valid_scope_filters_by_availability(): void
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

        $validRates = Rate::valid()->get();
        $this->assertEquals(1, $validRates->count());
        $this->assertTrue($validRates->first()->available);
    }

    public function test_valid_scope_filters_by_date_range(): void
    {
        // Rate valid in the past (should not be included)
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
            'valid_from' => Carbon::now()->subWeek(),
            'valid_to' => Carbon::now()->subDay()
        ]);

        // Rate valid now
        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 55.00,
            'total_one_way' => 65.00,
            'cost_vehicle_round_trip' => 100.00,
            'total_round_trip' => 120.00,
            'num_vehicles' => 1,
            'available' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addWeek()
        ]);

        // Rate valid in the future (should not be included yet)
        Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => 60.00,
            'total_one_way' => 70.00,
            'cost_vehicle_round_trip' => 110.00,
            'total_round_trip' => 130.00,
            'num_vehicles' => 1,
            'available' => true,
            'valid_from' => Carbon::now()->addWeek(),
            'valid_to' => Carbon::now()->addMonth()
        ]);

        $validRates = Rate::valid()->get();
        $this->assertEquals(1, $validRates->count());
        $this->assertEquals(55.00, $validRates->first()->cost_vehicle_one_way);
    }

    public function test_zone_based_scope(): void
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

        Rate::create([
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
        ]);

        $zoneBasedRates = Rate::zoneBased()->get();
        $this->assertEquals(2, $zoneBasedRates->count()); // Both have zone IDs
    }

    public function test_location_specific_scope(): void
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

        Rate::create([
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
        ]);

        $locationSpecificRates = Rate::locationSpecific()->get();
        $this->assertEquals(1, $locationSpecificRates->count());
        $this->assertEquals(55.00, $locationSpecificRates->first()->cost_vehicle_one_way);
    }

    public function test_find_for_route_prioritizes_location_specific_rates(): void
    {
        // Zone-based rate
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

        // Location-specific rate (should take priority)
        Rate::create([
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
        ]);

        $rates = Rate::findForRoute(
            $this->serviceType->id,
            $this->fromLocation->id,
            $this->toLocation->id
        );

        $this->assertEquals(1, $rates->count());
        $this->assertEquals(55.00, $rates->first()->cost_vehicle_one_way);
        $this->assertTrue($rates->first()->isLocationSpecific());
    }

    public function test_find_for_route_falls_back_to_zone_rates(): void
    {
        // Zone-based rate only
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

        $rates = Rate::findForRoute(
            $this->serviceType->id,
            $this->fromLocation->id,
            $this->toLocation->id
        );

        $this->assertEquals(1, $rates->count());
        $this->assertEquals(50.00, $rates->first()->cost_vehicle_one_way);
        $this->assertTrue($rates->first()->isZoneBased());
        $this->assertFalse($rates->first()->isLocationSpecific());
    }

    public function test_find_for_zones(): void
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

        $rates = Rate::findForZones(
            $this->serviceType->id,
            $this->fromZone->id,
            $this->toZone->id
        );

        $this->assertEquals(1, $rates->count());
        $this->assertEquals(50.00, $rates->first()->cost_vehicle_one_way);
    }

    public function test_get_by_service_type(): void
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

        $rates = Rate::getByServiceType($this->serviceType->id);

        $this->assertEquals(1, $rates->count());
        $this->assertEquals($this->serviceType->id, $rates->first()->service_type_id);
    }

    public function test_is_valid_for_date(): void
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
            'available' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addWeek()
        ]);

        $this->assertTrue($rate->isValidForDate(Carbon::now()));
        $this->assertFalse($rate->isValidForDate(Carbon::now()->subWeek()));
        $this->assertFalse($rate->isValidForDate(Carbon::now()->addMonth()));
    }

    public function test_is_valid_for_date_with_unavailable_rate(): void
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
            'available' => false
        ]);

        $this->assertFalse($rate->isValidForDate(Carbon::now()));
    }

    public function test_get_formatted_price(): void
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

        $this->assertEquals('60.00', $rate->getFormattedPrice('one_way'));
        $this->assertEquals('110.00', $rate->getFormattedPrice('round_trip'));
        $this->assertEquals('60.00', $rate->getFormattedPrice()); // Default to one_way
    }

    public function test_decimal_casting(): void
    {
        $rate = Rate::create([
            'service_type_id' => $this->serviceType->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'from_zone_id' => $this->fromZone->id,
            'to_zone_id' => $this->toZone->id,
            'cost_vehicle_one_way' => '50.123',
            'total_one_way' => '60.456',
            'cost_vehicle_round_trip' => '90.789',
            'total_round_trip' => '110.999',
            'num_vehicles' => 1,
            'available' => true
        ]);

        // Values should be cast to decimal with 2 places
        $this->assertEquals('50.12', $rate->cost_vehicle_one_way);
        $this->assertEquals('60.46', $rate->total_one_way);
        $this->assertEquals('90.79', $rate->cost_vehicle_round_trip);
        $this->assertEquals('111.00', $rate->total_round_trip);
    }

    public function test_caching_behavior(): void
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

        // First call should cache the result
        $rates1 = Rate::findForRoute(
            $this->serviceType->id,
            $this->fromLocation->id,
            $this->toLocation->id
        );

        // Second call should use cache
        $rates2 = Rate::findForRoute(
            $this->serviceType->id,
            $this->fromLocation->id,
            $this->toLocation->id
        );

        $this->assertEquals($rates1->count(), $rates2->count());
        $this->assertEquals($rates1->first()->id, $rates2->first()->id);
    }
}