<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Location;
use App\Models\Zone;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los IDs necesarios
        $roundTripService = ServiceType::where('code', 'RT')->first();
        $oneWayService = ServiceType::where('code', 'OW')->first();
        $hotelToHotelService = ServiceType::where('code', 'HTH')->first();

        $standardPrivate = VehicleType::where('name', 'standard private')->first();
        $crafter = VehicleType::where('name', 'CRAFTER')->first();
        $vipPrivate = VehicleType::where('name', 'vip private')->first();
        $economicalLimo = VehicleType::where('name', 'Economical Limo')->first();
        $limousines = VehicleType::where('name', 'limousines')->first();

        // Get existing zones for creating zone-based rates
        $puntaCancun = Zone::where('name', 'Punta Cancun')->first();
        $cancunCity = Zone::where('name', 'Cancun City')->first(); 
        $playaDelCarmen = Zone::where('name', 'Playa del Carmen')->first();
        $tulum = Zone::where('name', 'Tulum')->first();
        $akumal = Zone::where('name', 'Akumal')->first();

        if (!$puntaCancun || !$cancunCity || !$playaDelCarmen) {
            $this->command->warn('Required zones not found. Creating basic zone-based rates with available zones.');
            // Get any available zones
            $zones = Zone::take(3)->get();
            if ($zones->count() < 2) {
                $this->command->error('Not enough zones available. Please ensure zones are seeded first.');
                return;
            }
            $puntaCancun = $zones[0];
            $cancunCity = $zones[1];
            $playaDelCarmen = $zones->count() > 2 ? $zones[2] : $zones[1];
        }

        // Create zone-based rates for Round Trip service
        if ($roundTripService && $standardPrivate) {
            // Punta Cancun to other zones
            $this->createZoneRatesForRoute(
                $roundTripService,
                $puntaCancun,
                $cancunCity,
                [
                    ['vehicle' => $standardPrivate, 'ow_cost' => 45.00, 'ow_total' => 45, 'rt_cost' => 85.00, 'rt_total' => 85],
                    ['vehicle' => $vipPrivate, 'ow_cost' => 120.00, 'ow_total' => 120, 'rt_cost' => 220.00, 'rt_total' => 220],
                ]
            );

            if ($playaDelCarmen) {
                $this->createZoneRatesForRoute(
                    $roundTripService,
                    $puntaCancun,
                    $playaDelCarmen,
                    [
                        ['vehicle' => $standardPrivate, 'ow_cost' => 65.00, 'ow_total' => 65, 'rt_cost' => 120.00, 'rt_total' => 120],
                        ['vehicle' => $crafter, 'ow_cost' => 155.00, 'ow_total' => 155, 'rt_cost' => 310.00, 'rt_total' => 310],
                        ['vehicle' => $vipPrivate, 'ow_cost' => 180.00, 'ow_total' => 180, 'rt_cost' => 350.00, 'rt_total' => 350],
                    ]
                );
            }

            if ($akumal) {
                $this->createZoneRatesForRoute(
                    $roundTripService,
                    $puntaCancun,
                    $akumal,
                    [
                        ['vehicle' => $standardPrivate, 'ow_cost' => 82.00, 'ow_total' => 82, 'rt_cost' => 150.00, 'rt_total' => 150],
                        ['vehicle' => $vipPrivate, 'ow_cost' => 200.00, 'ow_total' => 200, 'rt_cost' => 390.00, 'rt_total' => 390],
                        ['vehicle' => $economicalLimo, 'ow_cost' => 295.00, 'ow_total' => 295, 'rt_cost' => 590.00, 'rt_total' => 590],
                    ]
                );
            }

            if ($tulum) {
                $this->createZoneRatesForRoute(
                    $roundTripService,
                    $puntaCancun,
                    $tulum,
                    [
                        ['vehicle' => $standardPrivate, 'ow_cost' => 95.00, 'ow_total' => 95, 'rt_cost' => 180.00, 'rt_total' => 180],
                        ['vehicle' => $vipPrivate, 'ow_cost' => 220.00, 'ow_total' => 220, 'rt_cost' => 420.00, 'rt_total' => 420],
                        ['vehicle' => $limousines, 'ow_cost' => 725.00, 'ow_total' => 725, 'rt_cost' => 1500.00, 'rt_total' => 1500],
                    ]
                );
            }
        }

        // Create One Way service rates
        if ($oneWayService && $standardPrivate && $playaDelCarmen) {
            $this->createZoneRatesForRoute(
                $oneWayService,
                $puntaCancun,
                $playaDelCarmen,
                [
                    ['vehicle' => $standardPrivate, 'ow_cost' => 65.00, 'ow_total' => 65, 'rt_cost' => 120.00, 'rt_total' => 120],
                    ['vehicle' => $vipPrivate, 'ow_cost' => 180.00, 'ow_total' => 180, 'rt_cost' => 350.00, 'rt_total' => 350],
                ]
            );
        }

        // Create Hotel to Hotel service rates
        if ($hotelToHotelService && $standardPrivate && $playaDelCarmen && $akumal) {
            $this->createZoneRatesForRoute(
                $hotelToHotelService,
                $playaDelCarmen,
                $akumal,
                [
                    ['vehicle' => $standardPrivate, 'ow_cost' => 45.00, 'ow_total' => 45, 'rt_cost' => 85.00, 'rt_total' => 85],
                    ['vehicle' => $vipPrivate, 'ow_cost' => 120.00, 'ow_total' => 120, 'rt_cost' => 220.00, 'rt_total' => 220],
                ]
            );
        }

        // Create some location-specific rates as examples (overrides)
        $locations = Location::with('zone')->take(4)->get();
        if ($locations->count() >= 2 && $hotelToHotelService && $standardPrivate) {
            // Create location-specific rates for hotels in the same zone (hotel-to-hotel within zone)
            $sameZoneLocations = $locations->where('zone_id', $locations->first()->zone_id);
            if ($sameZoneLocations->count() >= 2) {
                $loc1 = $sameZoneLocations->first();
                $loc2 = $sameZoneLocations->skip(1)->first();
                
                if ($loc1 && $loc2) {
                    $this->createLocationSpecificRates(
                        $hotelToHotelService,
                        $loc1,
                        $loc2,
                        [
                            ['vehicle' => $standardPrivate, 'ow_cost' => 65.00, 'ow_total' => 65, 'rt_cost' => 125.00, 'rt_total' => 125],
                            ['vehicle' => $vipPrivate, 'ow_cost' => 180.00, 'ow_total' => 180, 'rt_cost' => 350.00, 'rt_total' => 350],
                        ]
                    );
                }
            }
        }
    }

    private function createZoneRatesForRoute($serviceType, $fromZone, $toZone, $vehicleRates): void
    {
        foreach ($vehicleRates as $rate) {
            if (!$rate['vehicle']) continue; // Skip if vehicle doesn't exist

            Rate::create([
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $rate['vehicle']->id,
                'from_zone_id' => $fromZone->id,
                'to_zone_id' => $toZone->id,
                'cost_vehicle_one_way' => $rate['ow_cost'],
                'total_one_way' => $rate['ow_total'],
                'cost_vehicle_round_trip' => $rate['rt_cost'],
                'total_round_trip' => $rate['rt_total'],
                'num_vehicles' => 1,
                'available' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ]);
        }
    }

    private function createLocationSpecificRates($serviceType, $fromLocation, $toLocation, $vehicleRates): void
    {
        foreach ($vehicleRates as $rate) {
            if (!$rate['vehicle']) continue; // Skip if vehicle doesn't exist

            Rate::create([
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $rate['vehicle']->id,
                'from_zone_id' => $fromLocation->zone_id,
                'to_zone_id' => $toLocation->zone_id,
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $toLocation->id,
                'cost_vehicle_one_way' => $rate['ow_cost'],
                'total_one_way' => $rate['ow_total'],
                'cost_vehicle_round_trip' => $rate['rt_cost'],
                'total_round_trip' => $rate['rt_total'],
                'num_vehicles' => 1,
                'available' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ]);
        }
    }
}