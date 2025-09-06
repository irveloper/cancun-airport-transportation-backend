<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\Airport;

class AirportRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get service types (arrival, departure, round-trip use airports)
        $roundTrip = ServiceType::where('code', 'RT')->first();
        $oneWay = ServiceType::where('code', 'OW')->first(); // Can be arrival or departure

        // Get vehicle types
        $standardPrivate = VehicleType::where('name', 'standard private')->first();
        $crafter = VehicleType::where('name', 'CRAFTER')->first();
        $vipPrivate = VehicleType::where('name', 'vip private')->first();
        $economicalLimo = VehicleType::where('name', 'Economical Limo')->first();
        $limousines = VehicleType::where('name', 'limousines')->first();

        // Vehicle pricing multipliers (airport transfers typically cost more)
        $vehicleMultipliers = [
            $standardPrivate->id => ['name' => 'Standard Private', 'ow_multiplier' => 1.0, 'rt_multiplier' => 1.85],
            $crafter->id => ['name' => 'CRAFTER', 'ow_multiplier' => 1.9, 'rt_multiplier' => 3.8],
            $vipPrivate->id => ['name' => 'VIP Private', 'ow_multiplier' => 2.4, 'rt_multiplier' => 4.6],
            $economicalLimo->id => ['name' => 'Economical Limo', 'ow_multiplier' => 3.6, 'rt_multiplier' => 7.0],
            $limousines->id => ['name' => 'Limousines', 'ow_multiplier' => 8.8, 'rt_multiplier' => 18.3],
        ];

        // Define airport zones by name - these are the zones where airports are conceptually located
        $airportZoneNames = [
            'CUN' => [
                'airport_name' => 'Cancun Airport',
                'zone_name' => 'Punta Cancun', // Punta Cancun - closest to airport operations
                'city_zone_names' => ['Punta Cancun', 'Cancun City'], // Main zones for this airport
            ],
            'PCM' => [
                'airport_name' => 'Playa del Carmen Airport',
                'zone_name' => 'Playa del Carmen', // Playa del Carmen
                'city_zone_names' => ['Playa del Carmen'], // Main zones for this airport
            ]
        ];

        // Convert airport zone names to actual zone data with auto-increment IDs
        $airportZones = [];
        foreach ($airportZoneNames as $code => $data) {
            $zone = Zone::where('name', $data['zone_name'])->first();
            if ($zone) {
                $airportZones[$code] = [
                    'airport_name' => $data['airport_name'],
                    'zone_id' => $zone->id,
                    'city_zones' => Zone::whereIn('name', $data['city_zone_names'])->pluck('id')->toArray(),
                ];
            }
        }

        // All destination zones (everywhere people travel to) by name
        $destinationZoneNames = [
            'Punta Cancun' => ['base_price' => 35],      // Hotel Zone
            'Cancun City' => ['base_price' => 45],      // Downtown
            'Puerto Juarez' => ['base_price' => 50],    // Ferry
            'Playa del Carmen' => ['base_price' => 65],   // Playa
            'Akumal' => ['base_price' => 82],              // Akumal
            'Tulum' => ['base_price' => 95],               // Tulum Downtown  
            'Tulum Hotel Zone' => ['base_price' => 105],   // Tulum Beach
        ];

        // Convert destination zone names to actual zone data with auto-increment IDs
        $destinationZones = [];
        foreach ($destinationZoneNames as $zoneName => $data) {
            $zone = Zone::where('name', $zoneName)->first();
            if ($zone) {
                $destinationZones[$zone->id] = array_merge(['name' => $zoneName], $data);
            }
        }

        $this->command->info('Creating comprehensive airport-to-destination rates...');

        // Create rates from airports to all destination zones
        foreach ($airportZones as $airportCode => $airportData) {
            $airportZoneId = $airportData['zone_id'];
            
            foreach ($destinationZones as $destZoneId => $destData) {
                // Skip if airport zone is same as destination zone (but still create some local rates)
                if ($airportZoneId === $destZoneId) {
                    // Create minimal local rates for same-zone transfers
                    $basePrice = 25; // Local airport pickup
                } else {
                    $basePrice = $destData['base_price'];
                }

                foreach ([$roundTrip, $oneWay] as $serviceType) {
                    if (!$serviceType) continue;

                    foreach ($vehicleMultipliers as $vehicleTypeId => $vehicleData) {
                        // Calculate prices
                        $owPrice = round($basePrice * $vehicleData['ow_multiplier'], 2);
                        $rtPrice = round($basePrice * $vehicleData['rt_multiplier'], 2);

                        // Ensure minimum prices for airport transfers
                        $owPrice = max($owPrice, 35);
                        $rtPrice = max($rtPrice, 65);

                        try {
                            // Airport to Destination (Arrival/Departure)
                            Rate::create([
                                'service_type_id' => $serviceType->id,
                                'vehicle_type_id' => $vehicleTypeId,
                                'from_zone_id' => $airportZoneId,
                                'to_zone_id' => $destZoneId,
                                'cost_vehicle_one_way' => $owPrice,
                                'total_one_way' => $owPrice,
                                'cost_vehicle_round_trip' => $rtPrice,
                                'total_round_trip' => $rtPrice,
                                'num_vehicles' => 1,
                                'available' => true,
                                'valid_from' => now()->startOfYear(),
                                'valid_to' => now()->endOfYear(),
                            ]);

                            // Destination to Airport (Return/Departure) 
                            Rate::create([
                                'service_type_id' => $serviceType->id,
                                'vehicle_type_id' => $vehicleTypeId,
                                'from_zone_id' => $destZoneId,
                                'to_zone_id' => $airportZoneId,
                                'cost_vehicle_one_way' => $owPrice,
                                'total_one_way' => $owPrice,
                                'cost_vehicle_round_trip' => $rtPrice,
                                'total_round_trip' => $rtPrice,
                                'num_vehicles' => 1,
                                'available' => true,
                                'valid_from' => now()->startOfYear(),
                                'valid_to' => now()->endOfYear(),
                            ]);

                            $this->command->line("Created airport rates: {$airportData['airport_name']} ↔ {$destData['name']} ({$serviceType->code}) - {$vehicleData['name']}: \${$owPrice} OW / \${$rtPrice} RT");

                        } catch (\Exception $e) {
                            $this->command->error("Failed to create airport rate: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        // Create additional rates for airports to secondary Cancun zones (local area coverage)
        $cancunLocalZoneNames = ['Club Internacional Cancun', 'Coco Bongo Cancun', 'Playa Delfines Cancun', 
                                'Playa Langosta Cancun', 'Plaza Caracol Cancun', 'Plaza La Isla Cancun', 'Plazas Outlet Cancun'];
        $cancunAirportZone = Zone::where('name', 'Punta Cancun')->first(); // Punta Cancun (airport area)

        foreach ($cancunLocalZoneNames as $localZoneName) {
            try {
                $zone = Zone::where('name', $localZoneName)->first();
                if (!$zone || !$cancunAirportZone) continue;

                foreach ([$roundTrip, $oneWay] as $serviceType) {
                    if (!$serviceType) continue;

                    // Create basic rates for standard vehicles to local zones
                    foreach ([$standardPrivate->id, $vipPrivate->id] as $vehicleTypeId) {
                        $baseLocalPrice = 35; // Base price for local airport transfers
                        $multiplier = $vehicleTypeId === $standardPrivate->id ? 1.0 : 2.4;
                        
                        $owPrice = round($baseLocalPrice * $multiplier, 2);
                        $rtPrice = round($baseLocalPrice * $multiplier * 1.85, 2);

                        // Airport to Local Zone
                        Rate::create([
                            'service_type_id' => $serviceType->id,
                            'vehicle_type_id' => $vehicleTypeId,
                            'from_zone_id' => $cancunAirportZone->id,
                            'to_zone_id' => $zone->id,
                            'cost_vehicle_one_way' => $owPrice,
                            'total_one_way' => $owPrice,
                            'cost_vehicle_round_trip' => $rtPrice,
                            'total_round_trip' => $rtPrice,
                            'num_vehicles' => 1,
                            'available' => true,
                            'valid_from' => now()->startOfYear(),
                            'valid_to' => now()->endOfYear(),
                        ]);

                        // Local Zone to Airport
                        Rate::create([
                            'service_type_id' => $serviceType->id,
                            'vehicle_type_id' => $vehicleTypeId,
                            'from_zone_id' => $zone->id,
                            'to_zone_id' => $cancunAirportZone->id,
                            'cost_vehicle_one_way' => $owPrice,
                            'total_one_way' => $owPrice,
                            'cost_vehicle_round_trip' => $rtPrice,
                            'total_round_trip' => $rtPrice,
                            'num_vehicles' => 1,
                            'available' => true,
                            'valid_from' => now()->startOfYear(),
                            'valid_to' => now()->endOfYear(),
                        ]);

                        $vehicleName = $vehicleTypeId === $standardPrivate->id ? 'Standard Private' : 'VIP Private';
                        $this->command->line("Created local airport rate: Cancun Airport ↔ {$zone->name} ({$serviceType->code}) - {$vehicleName}: \${$owPrice} OW / \${$rtPrice} RT");
                    }
                }
            } catch (\Exception $e) {
                // Continue on error
            }
        }

        // Get airport zone IDs dynamically
        $puntaCancunZone = Zone::where('name', 'Punta Cancun')->first();
        $playaDelCarmenZone = Zone::where('name', 'Playa del Carmen')->first();
        
        $totalAirportRates = Rate::where(function($query) use ($puntaCancunZone, $playaDelCarmenZone) {
            if ($puntaCancunZone) {
                $query->orWhere('from_zone_id', $puntaCancunZone->id) // From Punta Cancun (Airport area)
                      ->orWhere('to_zone_id', $puntaCancunZone->id);   // To Punta Cancun (Airport area)
            }
            if ($playaDelCarmenZone) {
                $query->orWhere('from_zone_id', $playaDelCarmenZone->id) // From Playa del Carmen (Airport area)
                      ->orWhere('to_zone_id', $playaDelCarmenZone->id);  // To Playa del Carmen (Airport area)
            }
        })->count();

        $totalRates = Rate::count();
        $this->command->info("Airport rate seeding completed!");
        $this->command->line("- Airport-related rates: {$totalAirportRates}");
        $this->command->line("- Total rates in database: {$totalRates}");
        
        // Show summary by service type for airport rates
        foreach ([$roundTrip, $oneWay] as $serviceType) {
            if ($serviceType) {
                $count = Rate::where('service_type_id', $serviceType->id)
                    ->where(function($query) use ($puntaCancunZone, $playaDelCarmenZone) {
                        if ($puntaCancunZone) {
                            $query->orWhere('from_zone_id', $puntaCancunZone->id)->orWhere('to_zone_id', $puntaCancunZone->id);
                        }
                        if ($playaDelCarmenZone) {
                            $query->orWhere('from_zone_id', $playaDelCarmenZone->id)->orWhere('to_zone_id', $playaDelCarmenZone->id);
                        }
                    })->count();
                $this->command->line("- Airport {$serviceType->name} ({$serviceType->code}): {$count} rates");
            }
        }
    }
}