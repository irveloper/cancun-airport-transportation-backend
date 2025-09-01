<?php

if (! function_exists('create_test_city')) {
    /**
     * Helper function to create a test city
     */
    function create_test_city(array $attributes = []): \App\Models\City
    {
        return \App\Models\City::create(array_merge([
            'name' => 'Test City',
            'state' => 'FL',
            'country' => 'US'
        ], $attributes));
    }
}

if (! function_exists('create_test_zone')) {
    /**
     * Helper function to create a test zone
     */
    function create_test_zone(array $attributes = []): \App\Models\Zone
    {
        $cityId = $attributes['city_id'] ?? create_test_city()->id;
        
        return \App\Models\Zone::create(array_merge([
            'name' => 'Test Zone',
            'city_id' => $cityId,
            'active' => true
        ], $attributes));
    }
}

if (! function_exists('create_test_location')) {
    /**
     * Helper function to create a test location
     */
    function create_test_location(array $attributes = []): \App\Models\Location
    {
        $zoneId = $attributes['zone_id'] ?? create_test_zone()->id;
        
        return \App\Models\Location::create(array_merge([
            'name' => 'Test Location',
            'address' => '123 Test Street',
            'type' => 'hotel',
            'zone_id' => $zoneId,
            'latitude' => 25.7617,
            'longitude' => -80.1918,
            'active' => true
        ], $attributes));
    }
}

if (! function_exists('create_test_service_type')) {
    /**
     * Helper function to create a test service type
     */
    function create_test_service_type(array $attributes = []): \App\Models\ServiceType
    {
        return \App\Models\ServiceType::create(array_merge([
            'name' => 'Test Service',
            'code' => 'test',
            'active' => true
        ], $attributes));
    }
}

if (! function_exists('create_test_vehicle_type')) {
    /**
     * Helper function to create a test vehicle type
     */
    function create_test_vehicle_type(array $attributes = []): \App\Models\VehicleType
    {
        return \App\Models\VehicleType::create(array_merge([
            'name' => 'Test Vehicle',
            'code' => 'test',
            'capacity' => 4,
            'active' => true
        ], $attributes));
    }
}

if (! function_exists('create_test_rate')) {
    /**
     * Helper function to create a test rate
     */
    function create_test_rate(array $attributes = []): \App\Models\Rate
    {
        $serviceTypeId = $attributes['service_type_id'] ?? create_test_service_type()->id;
        $vehicleTypeId = $attributes['vehicle_type_id'] ?? create_test_vehicle_type()->id;
        $fromZoneId = $attributes['from_zone_id'] ?? create_test_zone(['name' => 'From Zone'])->id;
        $toZoneId = $attributes['to_zone_id'] ?? create_test_zone(['name' => 'To Zone'])->id;
        
        return \App\Models\Rate::create(array_merge([
            'service_type_id' => $serviceTypeId,
            'vehicle_type_id' => $vehicleTypeId,
            'from_zone_id' => $fromZoneId,
            'to_zone_id' => $toZoneId,
            'cost_vehicle_one_way' => 50.00,
            'total_one_way' => 60.00,
            'cost_vehicle_round_trip' => 90.00,
            'total_round_trip' => 110.00,
            'num_vehicles' => 1,
            'available' => true
        ], $attributes));
    }
}

if (! function_exists('create_test_airport')) {
    /**
     * Helper function to create a test airport
     */
    function create_test_airport(array $attributes = []): \App\Models\Airport
    {
        $cityId = $attributes['city_id'] ?? create_test_city()->id;
        
        return \App\Models\Airport::create(array_merge([
            'name' => 'Test Airport',
            'code' => 'TST',
            'city_id' => $cityId
        ], $attributes));
    }
}

if (! function_exists('assert_api_response_structure')) {
    /**
     * Helper function to assert standard API response structure
     */
    function assert_api_response_structure(\Illuminate\Testing\TestResponse $response, array $dataStructure = []): void
    {
        $expectedStructure = [
            'success',
            'message',
            'timestamp',
            'request_id'
        ];

        if (!empty($dataStructure)) {
            $expectedStructure['data'] = $dataStructure;
        }

        $response->assertJsonStructure($expectedStructure);
    }
}

if (! function_exists('assert_api_success')) {
    /**
     * Helper function to assert successful API response
     */
    function assert_api_success(\Illuminate\Testing\TestResponse $response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode);
        $response->assertJsonFragment(['success' => true]);
        
        // Verify timestamp format
        $timestamp = $response->json('timestamp');
        PHPUnit\Framework\Assert::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}000Z$/',
            $timestamp,
            'Timestamp should be in ISO format'
        );
        
        // Verify request_id exists
        PHPUnit\Framework\Assert::assertNotEmpty($response->json('request_id'));
    }
}

if (! function_exists('assert_api_error')) {
    /**
     * Helper function to assert error API response
     */
    function assert_api_error(\Illuminate\Testing\TestResponse $response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode);
        $response->assertJsonFragment(['success' => false]);
        PHPUnit\Framework\Assert::assertNotEmpty($response->json('message'));
    }
}

if (! function_exists('measure_execution_time')) {
    /**
     * Helper function to measure execution time of a callback
     */
    function measure_execution_time(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        return [
            'result' => $result,
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'memory_used_bytes' => $endMemory - $startMemory,
            'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024
        ];
    }
}

if (! function_exists('clean_test_database')) {
    /**
     * Helper function to clean the test database
     */
    function clean_test_database(): void
    {
        // Clear all tables in the correct order to avoid foreign key constraints
        $tables = [
            'rates',
            'locations', 
            'airports',
            'zones',
            'cities',
            'vehicle_types',
            'service_types'
        ];
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        foreach ($tables as $table) {
            if (\Schema::hasTable($table)) {
                \DB::table($table)->truncate();
            }
        }
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Clear cache
        \Cache::flush();
    }
}

if (! function_exists('seed_test_data')) {
    /**
     * Helper function to seed common test data
     */
    function seed_test_data(): array
    {
        $city = create_test_city(['name' => 'Miami']);
        
        $serviceType = create_test_service_type([
            'name' => 'Airport Transfer',
            'code' => 'airport'
        ]);
        
        $vehicleType = create_test_vehicle_type([
            'name' => 'Sedan',
            'code' => 'sedan'
        ]);
        
        $fromZone = create_test_zone([
            'name' => 'South Beach',
            'city_id' => $city->id
        ]);
        
        $toZone = create_test_zone([
            'name' => 'Airport Zone',
            'city_id' => $city->id
        ]);
        
        $fromLocation = create_test_location([
            'name' => 'Hotel Paradise',
            'zone_id' => $fromZone->id
        ]);
        
        $toLocation = create_test_location([
            'name' => 'Miami Airport',
            'type' => 'airport',
            'zone_id' => $toZone->id
        ]);
        
        $airport = create_test_airport([
            'name' => 'Miami International Airport',
            'code' => 'MIA',
            'city_id' => $city->id
        ]);
        
        $rate = create_test_rate([
            'service_type_id' => $serviceType->id,
            'vehicle_type_id' => $vehicleType->id,
            'from_zone_id' => $fromZone->id,
            'to_zone_id' => $toZone->id
        ]);
        
        return [
            'city' => $city,
            'service_type' => $serviceType,
            'vehicle_type' => $vehicleType,
            'from_zone' => $fromZone,
            'to_zone' => $toZone,
            'from_location' => $fromLocation,
            'to_location' => $toLocation,
            'airport' => $airport,
            'rate' => $rate
        ];
    }
}