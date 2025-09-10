<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;

class ComprehensiveRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get service types
        $roundTrip = ServiceType::where('code', 'RT')->first();
        $oneWay = ServiceType::where('code', 'OW')->first();
        $hotelToHotel = ServiceType::where('code', 'HTH')->first();

        // Get vehicle types
        $standardPrivate = VehicleType::where('name', 'standard private')->first();
        $crafter = VehicleType::where('name', 'CRAFTER')->first();
        $vipPrivate = VehicleType::where('name', 'vip private')->first();
        $economicalLimo = VehicleType::where('name', 'Economical Limo')->first();
        $limousines = VehicleType::where('name', 'limousines')->first();

        // Define main transportation zones by name and their base distances for pricing
        $zoneNames = [
            'Punta Cancun' => ['base_price' => 0],     // Airport/Hotel Zone
            'Cancun City' => ['base_price' => 20],    // Downtown Cancun
            'Puerto Juarez' => ['base_price' => 25],  // Ferry to Isla Mujeres
            'Playa del Carmen' => ['base_price' => 65], // Playa del Carmen
            'Akumal' => ['base_price' => 82],            // Akumal
            'Tulum' => ['base_price' => 95],             // Tulum Downtown
            'Tulum Hotel Zone' => ['base_price' => 105], // Tulum Beach
        ];

        // Get zones by name (using auto-increment IDs)
        $mainZones = [];
        foreach ($zoneNames as $zoneName => $data) {
            $zone = Zone::where('name', $zoneName)->first();
            if ($zone) {
                $mainZones[$zone->id] = array_merge(['name' => $zoneName], $data);
            }
        }

        // Vehicle pricing multipliers
        $vehicleMultipliers = [
            $standardPrivate->id => ['name' => 'Standard Private', 'ow_multiplier' => 1.0, 'rt_multiplier' => 1.85],
            $crafter->id => ['name' => 'CRAFTER', 'ow_multiplier' => 1.9, 'rt_multiplier' => 3.8],
            $vipPrivate->id => ['name' => 'VIP Private', 'ow_multiplier' => 2.4, 'rt_multiplier' => 4.6],
            $economicalLimo->id => ['name' => 'Economical Limo', 'ow_multiplier' => 3.6, 'rt_multiplier' => 7.0],
            $limousines->id => ['name' => 'Limousines', 'ow_multiplier' => 8.8, 'rt_multiplier' => 18.3],
        ];

        $this->command->info('Creating comprehensive zone-based rates...');

        // Create rates for all zone combinations
        foreach ($mainZones as $fromZoneId => $fromZoneData) {
            foreach ($mainZones as $toZoneId => $toZoneData) {
                if ($fromZoneId === $toZoneId) continue; // Skip same zone

                // Calculate base price based on distance/complexity
                $basePrice = abs($toZoneData['base_price'] - $fromZoneData['base_price']);
                if ($basePrice === 0) $basePrice = 25; // Minimum base price
                if ($basePrice < 35) $basePrice = 35; // Minimum reasonable price

                // Create rates for each service type and vehicle type
                foreach ([$roundTrip, $oneWay, $hotelToHotel] as $serviceType) {
                    if (!$serviceType) continue;

                    foreach ($vehicleMultipliers as $vehicleTypeId => $vehicleData) {
                        // Calculate prices
                        $owPrice = round($basePrice * $vehicleData['ow_multiplier'], 2);
                        $rtPrice = round($basePrice * $vehicleData['rt_multiplier'], 2);

                        // Add some variation for hotel-to-hotel (usually shorter distances)
                        if ($serviceType->code === 'HTH') {
                            $owPrice = round($owPrice * 0.75, 2); // 25% discount for hotel-to-hotel
                            $rtPrice = round($rtPrice * 0.75, 2);
                        }

                        // Ensure minimum prices
                        $owPrice = max($owPrice, 25);
                        $rtPrice = max($rtPrice, 45);

                        try {
                            Rate::create([
                                'service_type_id' => $serviceType->id,
                                'vehicle_type_id' => $vehicleTypeId,
                                'from_zone_id' => $fromZoneId,
                                'to_zone_id' => $toZoneId,
                                'cost_vehicle_one_way' => $owPrice,
                                'total_one_way' => $owPrice,
                                'cost_vehicle_round_trip' => $rtPrice,
                                'total_round_trip' => $rtPrice,
                                'num_vehicles' => 1,
                                'available' => true,
                                'valid_from' => now()->startOfYear(),
                                'valid_to' => now()->endOfYear(),
                            ]);

                            $this->command->line("Created rate: {$fromZoneData['name']} → {$toZoneData['name']} ({$serviceType->code}) - {$vehicleData['name']}: \${$owPrice} OW / \${$rtPrice} RT");
                        } catch (\Exception $e) {
                            $this->command->error("Failed to create rate for {$fromZoneData['name']} → {$toZoneData['name']}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        // Create some additional rates for other zones within Cancun area (local transport)
        $cancunLocalZoneNames = ['Club Internacional Cancun', 'Coco Bongo Cancun', 'Playa Delfines Cancun', 
                                'Playa Langosta Cancun', 'Plaza Caracol Cancun', 'Plaza La Isla Cancun', 'Plazas Outlet Cancun'];
        
        $puntaCancunZone = Zone::where('name', 'Punta Cancun')->first();
        
        foreach ($cancunLocalZoneNames as $zoneName) {
            $zone = Zone::where('name', $zoneName)->first();
            if ($zone && $puntaCancunZone && $hotelToHotel && $standardPrivate) {
                try {
                    Rate::create([
                        'service_type_id' => $hotelToHotel->id,
                        'vehicle_type_id' => $standardPrivate->id,
                        'from_zone_id' => $puntaCancunZone->id, // From Punta Cancun
                        'to_zone_id' => $zone->id,
                        'cost_vehicle_one_way' => 35.00,
                        'total_one_way' => 35.00,
                        'cost_vehicle_round_trip' => 65.00,
                        'total_round_trip' => 65.00,
                        'num_vehicles' => 1,
                        'available' => true,
                        'valid_from' => now()->startOfYear(),
                        'valid_to' => now()->endOfYear(),
                    ]);

                    // Reverse direction
                    Rate::create([
                        'service_type_id' => $hotelToHotel->id,
                        'vehicle_type_id' => $standardPrivate->id,
                        'from_zone_id' => $zone->id,
                        'to_zone_id' => $puntaCancunZone->id, // To Punta Cancun
                        'cost_vehicle_one_way' => 35.00,
                        'total_one_way' => 35.00,
                        'cost_vehicle_round_trip' => 65.00,
                        'total_round_trip' => 65.00,
                        'num_vehicles' => 1,
                        'available' => true,
                        'valid_from' => now()->startOfYear(),
                        'valid_to' => now()->endOfYear(),
                    ]);
                } catch (\Exception $e) {
                    // Continue on error
                }
            }
        }

        $totalRates = Rate::count();
        $this->command->info("Comprehensive rate seeding completed! Total rates in database: {$totalRates}");
        
        // Show summary by service type
        foreach ([$roundTrip, $oneWay, $hotelToHotel] as $serviceType) {
            if ($serviceType) {
                $count = Rate::where('service_type_id', $serviceType->id)->count();
                $this->command->line("- {$serviceType->name} ({$serviceType->code}): {$count} rates");
            }
        }
    }
}