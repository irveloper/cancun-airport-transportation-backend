<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Location;

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

        // Obtener algunas ubicaciones de ejemplo
        $locations = Location::take(5)->get();
        
        if ($locations->count() < 2) {
            $this->command->warn('Not enough locations found. Please run LocationSeeder first.');
            return;
        }

        // Crear rates para diferentes rutas y servicios
        $this->createRatesForRoute(
            $roundTripService,
            $locations[0], // Cancun Airport (ejemplo)
            $locations[1], // Akumal (ejemplo)
            [
                ['vehicle' => $standardPrivate, 'ow_cost' => 82.00, 'ow_total' => 82, 'rt_cost' => 150.00, 'rt_total' => 150],
                ['vehicle' => $crafter, 'ow_cost' => 155.00, 'ow_total' => 155, 'rt_cost' => 310.00, 'rt_total' => 310],
                ['vehicle' => $vipPrivate, 'ow_cost' => 200.00, 'ow_total' => 200, 'rt_cost' => 390.00, 'rt_total' => 390],
                ['vehicle' => $economicalLimo, 'ow_cost' => 295.00, 'ow_total' => 295, 'rt_cost' => 590.00, 'rt_total' => 590],
                ['vehicle' => $limousines, 'ow_cost' => 725.00, 'ow_total' => 725, 'rt_cost' => 1500.00, 'rt_total' => 1500],
            ]
        );

        $this->createRatesForRoute(
            $oneWayService,
            $locations[0],
            $locations[1],
            [
                ['vehicle' => $standardPrivate, 'ow_cost' => 82.00, 'ow_total' => 82, 'rt_cost' => 150.00, 'rt_total' => 150],
                ['vehicle' => $crafter, 'ow_cost' => 155.00, 'ow_total' => 155, 'rt_cost' => 310.00, 'rt_total' => 310],
                ['vehicle' => $vipPrivate, 'ow_cost' => 200.00, 'ow_total' => 200, 'rt_cost' => 390.00, 'rt_total' => 390],
            ]
        );

        // Hotel to Hotel service (entre ubicaciones no-aeropuerto)
        if ($locations->count() >= 4) {
            $this->createRatesForRoute(
                $hotelToHotelService,
                $locations[2],
                $locations[3],
                [
                    ['vehicle' => $standardPrivate, 'ow_cost' => 65.00, 'ow_total' => 65, 'rt_cost' => 125.00, 'rt_total' => 125],
                    ['vehicle' => $vipPrivate, 'ow_cost' => 180.00, 'ow_total' => 180, 'rt_cost' => 350.00, 'rt_total' => 350],
                ]
            );
        }

        // Crear algunas rutas adicionales para tener más opciones
        for ($i = 0; $i < min(3, $locations->count() - 1); $i++) {
            for ($j = $i + 1; $j < min($i + 3, $locations->count()); $j++) {
                if ($i !== $j) {
                    $this->createRatesForRoute(
                        $roundTripService,
                        $locations[$i],
                        $locations[$j],
                        [
                            ['vehicle' => $standardPrivate, 'ow_cost' => rand(60, 120), 'ow_total' => rand(60, 120), 'rt_cost' => rand(120, 240), 'rt_total' => rand(120, 240)],
                            ['vehicle' => $vipPrivate, 'ow_cost' => rand(150, 250), 'ow_total' => rand(150, 250), 'rt_cost' => rand(300, 500), 'rt_total' => rand(300, 500)],
                        ]
                    );
                }
            }
        }
    }

    private function createRatesForRoute($serviceType, $fromLocation, $toLocation, $vehicleRates): void
    {
        foreach ($vehicleRates as $rate) {
            Rate::create([
                'service_type_id' => $serviceType->id,
                'vehicle_type_id' => $rate['vehicle']->id,
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