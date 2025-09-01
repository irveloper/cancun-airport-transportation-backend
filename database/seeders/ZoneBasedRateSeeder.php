<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\VehicleType;
use App\Models\Zone;

class ZoneBasedRateSeeder extends Seeder
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
        $standardVan = VehicleType::where('code', 'ES')->first();
        $crafter = VehicleType::where('code', 'ES')->first(); // Assuming CRAFTER is also ES type
        $vipSuburban = VehicleType::where('code', 'VP')->first();
        $economicalLimo = VehicleType::where('code', 'LS')->first();
        $limousine = VehicleType::where('code', 'LS')->first();

        // Get existing zones
        $puntaCancun = Zone::where('name', 'Punta Cancun')->first(); // Zone ID 507
        $cancunCity = Zone::where('name', 'Cancun City')->first(); // Zone ID 1447
        $playaDelCarmen = Zone::where('name', 'Playa del Carmen')->first(); // Zone ID 908
        $tulum = Zone::where('name', 'Tulum')->first(); // Zone ID 1 or 2
        $akumal = Zone::where('name', 'Akumal')->first(); // Zone ID 3

        if (!$puntaCancun || !$cancunCity || !$playaDelCarmen || !$tulum || !$akumal) {
            $this->command->error('Required zones not found. Please ensure zones are seeded first.');
            return;
        }

        // Sample zone-based rates for Punta Cancun to different zones
        if ($roundTrip && $standardVan && $puntaCancun) {
            // Punta Cancun to Akumal
            Rate::create([
                'service_type_id' => $roundTrip->id,
                'vehicle_type_id' => $standardVan->id,
                'from_zone_id' => $puntaCancun->id,
                'to_zone_id' => $akumal->id,
                'cost_vehicle_one_way' => 82.00,
                'total_one_way' => 82,
                'cost_vehicle_round_trip' => 150.00,
                'total_round_trip' => 150,
                'num_vehicles' => 1,
                'available' => true,
            ]);

            // Punta Cancun to Playa del Carmen
            Rate::create([
                'service_type_id' => $roundTrip->id,
                'vehicle_type_id' => $standardVan->id,
                'from_zone_id' => $puntaCancun->id,
                'to_zone_id' => $playaDelCarmen->id,
                'cost_vehicle_one_way' => 65.00,
                'total_one_way' => 65,
                'cost_vehicle_round_trip' => 120.00,
                'total_round_trip' => 120,
                'num_vehicles' => 1,
                'available' => true,
            ]);

            // Punta Cancun to Tulum
            Rate::create([
                'service_type_id' => $roundTrip->id,
                'vehicle_type_id' => $standardVan->id,
                'from_zone_id' => $puntaCancun->id,
                'to_zone_id' => $tulum->id,
                'cost_vehicle_one_way' => 95.00,
                'total_one_way' => 95,
                'cost_vehicle_round_trip' => 180.00,
                'total_round_trip' => 180,
                'num_vehicles' => 1,
                'available' => true,
            ]);
        }

        // VIP Suburban rates for the same routes
        if ($roundTrip && $vipSuburban && $puntaCancun) {
            // Punta Cancun to Akumal
            Rate::create([
                'service_type_id' => $roundTrip->id,
                'vehicle_type_id' => $vipSuburban->id,
                'from_zone_id' => $puntaCancun->id,
                'to_zone_id' => $akumal->id,
                'cost_vehicle_one_way' => 200.00,
                'total_one_way' => 200,
                'cost_vehicle_round_trip' => 390.00,
                'total_round_trip' => 390,
                'num_vehicles' => 1,
                'available' => true,
            ]);

            // Punta Cancun to Playa del Carmen
            Rate::create([
                'service_type_id' => $roundTrip->id,
                'vehicle_type_id' => $vipSuburban->id,
                'from_zone_id' => $puntaCancun->id,
                'to_zone_id' => $playaDelCarmen->id,
                'cost_vehicle_one_way' => 180.00,
                'total_one_way' => 180,
                'cost_vehicle_round_trip' => 350.00,
                'total_round_trip' => 350,
                'num_vehicles' => 1,
                'available' => true,
            ]);
        }

        // Hotel-to-hotel rates between zones
        if ($hotelToHotel && $standardVan) {
            // Akumal to Playa del Carmen
            Rate::create([
                'service_type_id' => $hotelToHotel->id,
                'vehicle_type_id' => $standardVan->id,
                'from_zone_id' => $akumal->id,
                'to_zone_id' => $playaDelCarmen->id,
                'cost_vehicle_one_way' => 45.00,
                'total_one_way' => 45,
                'cost_vehicle_round_trip' => 85.00,
                'total_round_trip' => 85,
                'num_vehicles' => 1,
                'available' => true,
            ]);

            // Playa del Carmen to Tulum
            Rate::create([
                'service_type_id' => $hotelToHotel->id,
                'vehicle_type_id' => $standardVan->id,
                'from_zone_id' => $playaDelCarmen->id,
                'to_zone_id' => $tulum->id,
                'cost_vehicle_one_way' => 35.00,
                'total_one_way' => 35,
                'cost_vehicle_round_trip' => 65.00,
                'total_round_trip' => 65,
                'num_vehicles' => 1,
                'available' => true,
            ]);
        }

        $this->command->info('Zone-based rates seeded successfully!');
    }
}
