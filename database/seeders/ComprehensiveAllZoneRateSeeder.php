<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\City;

class ComprehensiveAllZoneRateSeeder extends Seeder
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

        if (!$roundTrip || !$oneWay || !$hotelToHotel || !$standardPrivate) {
            $this->command->error('Required service types or vehicle types not found. Please run other seeders first.');
            return;
        }

        // Get all zones with their cities
        $zones = Zone::with('city')->get();
        $cities = City::all()->keyBy('name');

        $this->command->info("Creating comprehensive rates for all {$zones->count()} zones...");

        // Define city-based pricing tiers
        $cityPricingTiers = [
            'Cancun' => [
                'base_price' => 35,
                'tier' => 1,
                'is_airport_city' => true,
            ],
            'Isla Mujeres' => [
                'base_price' => 50,
                'tier' => 2,
                'is_airport_city' => false,
            ],
            'Playa del Carmen' => [
                'base_price' => 65,
                'tier' => 3,
                'is_airport_city' => false,
            ],
            'Akumal' => [
                'base_price' => 82,
                'tier' => 4,
                'is_airport_city' => false,
            ],
            'Tulum' => [
                'base_price' => 95,
                'tier' => 5,
                'is_airport_city' => false,
            ],
        ];

        // Vehicle pricing multipliers
        $vehicleMultipliers = [
            $standardPrivate->id => ['name' => 'Standard Private', 'ow_multiplier' => 1.0, 'rt_multiplier' => 1.85],
            $crafter->id => ['name' => 'CRAFTER', 'ow_multiplier' => 1.9, 'rt_multiplier' => 3.8],
            $vipPrivate->id => ['name' => 'VIP Private', 'ow_multiplier' => 2.4, 'rt_multiplier' => 4.6],
            $economicalLimo->id => ['name' => 'Economical Limo', 'ow_multiplier' => 3.6, 'rt_multiplier' => 7.0],
            $limousines->id => ['name' => 'Limousines', 'ow_multiplier' => 8.8, 'rt_multiplier' => 18.3],
        ];

        $ratesCreated = 0;
        $defaultRatesCreated = 0;
        $specificRatesCreated = 0;

        // Create rates for ALL zone combinations
        foreach ($zones as $fromZone) {
            foreach ($zones as $toZone) {
                if ($fromZone->id === $toZone->id) {
                    // Same zone - create special intra-zone rates for local transport
                    $this->createIntraZoneRates($fromZone, $roundTrip, $oneWay, $hotelToHotel, $vehicleMultipliers, $ratesCreated, $defaultRatesCreated);
                    continue;
                }

                // Calculate base price based on city tiers
                $fromCityTier = $cityPricingTiers[$fromZone->city->name] ?? ['base_price' => 100, 'tier' => 6];
                $toCityTier = $cityPricingTiers[$toZone->city->name] ?? ['base_price' => 100, 'tier' => 6];
                
                // Calculate price based on distance between cities
                $tierDifference = abs($fromCityTier['tier'] - $toCityTier['tier']);
                $basePrice = max($fromCityTier['base_price'], $toCityTier['base_price']);
                
                // Add distance multiplier
                if ($tierDifference === 0) {
                    // Same city - local transport
                    $basePrice = min($basePrice, 45);
                } else {
                    // Different cities - add distance cost
                    $basePrice += ($tierDifference * 15);
                }

                // Ensure minimum reasonable price
                $basePrice = max($basePrice, 25);

                // Create rates for each service type and vehicle type
                foreach ([$roundTrip, $oneWay, $hotelToHotel] as $serviceType) {
                    foreach ($vehicleMultipliers as $vehicleTypeId => $vehicleData) {
                        // Calculate prices
                        $owPrice = round($basePrice * $vehicleData['ow_multiplier'], 2);
                        $rtPrice = round($basePrice * $vehicleData['rt_multiplier'], 2);

                        // Adjust for service type
                        if ($serviceType->code === 'HTH') {
                            // Hotel-to-hotel usually cheaper than airport transfers
                            $owPrice = round($owPrice * 0.85, 2);
                            $rtPrice = round($rtPrice * 0.85, 2);
                        }

                        // Ensure minimum prices
                        $owPrice = max($owPrice, 25);
                        $rtPrice = max($rtPrice, 45);

                        try {
                            // Create DEFAULT rate (no specific dates)
                            $defaultRate = Rate::create([
                                'service_type_id' => $serviceType->id,
                                'vehicle_type_id' => $vehicleTypeId,
                                'from_zone_id' => $fromZone->id,
                                'to_zone_id' => $toZone->id,
                                'cost_vehicle_one_way' => $owPrice,
                                'total_one_way' => $owPrice,
                                'cost_vehicle_round_trip' => $rtPrice,
                                'total_round_trip' => $rtPrice,
                                'num_vehicles' => 1,
                                'available' => true,
                                'valid_from' => null, // NULL = default rate
                                'valid_to' => null,   // NULL = default rate
                            ]);
                            $defaultRatesCreated++;

                            // Also create some SPECIFIC dated rates for popular routes (higher prices)
                            if (in_array($fromZone->city->name, ['Cancun', 'Playa del Carmen', 'Tulum']) && 
                                in_array($toZone->city->name, ['Cancun', 'Playa del Carmen', 'Tulum']) &&
                                $fromZone->city->name !== $toZone->city->name) {
                                
                                // High season rates (December-January)
                                $highSeasonOw = round($owPrice * 1.3, 2);
                                $highSeasonRt = round($rtPrice * 1.3, 2);
                                
                                Rate::create([
                                    'service_type_id' => $serviceType->id,
                                    'vehicle_type_id' => $vehicleTypeId,
                                    'from_zone_id' => $fromZone->id,
                                    'to_zone_id' => $toZone->id,
                                    'cost_vehicle_one_way' => $highSeasonOw,
                                    'total_one_way' => $highSeasonOw,
                                    'cost_vehicle_round_trip' => $highSeasonRt,
                                    'total_round_trip' => $highSeasonRt,
                                    'num_vehicles' => 1,
                                    'available' => true,
                                    'valid_from' => now()->startOfYear()->addMonth(11), // December 1st
                                    'valid_to' => now()->startOfYear()->addMonth(13)->subDay(), // January 31st
                                ]);
                                $specificRatesCreated++;
                            }

                            $ratesCreated++;

                        } catch (\Exception $e) {
                            $this->command->error("Failed to create rate for {$fromZone->name} → {$toZone->name}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->command->info("✅ Comprehensive rate seeding completed!");
        $this->command->line("- Total rates created: {$ratesCreated}");
        $this->command->line("- Default rates (no dates): {$defaultRatesCreated}");
        $this->command->line("- Specific dated rates: {$specificRatesCreated}");
        $this->command->line("- Zone combinations covered: " . ($zones->count() * ($zones->count() - 1)));
        
        // Show summary by service type
        foreach ([$roundTrip, $oneWay, $hotelToHotel] as $serviceType) {
            $count = Rate::where('service_type_id', $serviceType->id)->count();
            $this->command->line("- {$serviceType->name} ({$serviceType->code}): {$count} rates");
        }

        $this->command->info("✅ ALL zone combinations now have rates available!");
        $this->command->info("✅ Default rates will be used when no specific date matches");
    }

    /**
     * Create intra-zone rates for local transport within the same zone
     */
    private function createIntraZoneRates($zone, $roundTrip, $oneWay, $hotelToHotel, $vehicleMultipliers, &$ratesCreated, &$defaultRatesCreated)
    {
        // Base price for local transport within same zone
        $basePrice = 25; // Minimum local transport price

        // Create rates for each service type and vehicle type
        foreach ([$roundTrip, $oneWay, $hotelToHotel] as $serviceType) {
            foreach ($vehicleMultipliers as $vehicleTypeId => $vehicleData) {
                // Calculate prices
                $owPrice = round($basePrice * $vehicleData['ow_multiplier'], 2);
                $rtPrice = round($basePrice * $vehicleData['rt_multiplier'], 2);

                // Local transport is typically cheaper
                $owPrice = round($owPrice * 0.8, 2);
                $rtPrice = round($rtPrice * 0.8, 2);

                // Ensure minimum prices
                $owPrice = max($owPrice, 20);
                $rtPrice = max($rtPrice, 35);

                try {
                    // Create DEFAULT rate for same-zone transport
                    Rate::create([
                        'service_type_id' => $serviceType->id,
                        'vehicle_type_id' => $vehicleTypeId,
                        'from_zone_id' => $zone->id,
                        'to_zone_id' => $zone->id,
                        'cost_vehicle_one_way' => $owPrice,
                        'total_one_way' => $owPrice,
                        'cost_vehicle_round_trip' => $rtPrice,
                        'total_round_trip' => $rtPrice,
                        'num_vehicles' => 1,
                        'available' => true,
                        'valid_from' => null, // NULL = default rate
                        'valid_to' => null,   // NULL = default rate
                    ]);
                    $defaultRatesCreated++;
                    $ratesCreated++;

                } catch (\Exception $e) {
                    $this->command->error("Failed to create intra-zone rate for {$zone->name}: " . $e->getMessage());
                }
            }
        }
    }
}