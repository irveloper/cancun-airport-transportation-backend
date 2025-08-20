<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Zone;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $cancun = City::where('name', 'Cancun')->first();
        $islaMujeres = City::where('name', 'Isla Mujeres')->first();
        $playaDelCarmen = City::where('name', 'Playa del Carmen')->first();
        $tulum = City::where('name', 'Tulum')->first();
        $akumal = City::where('name', 'Akumal')->first();

        // Create missing cities
        if (!$islaMujeres) {
            $islaMujeres = City::create(['name' => 'Isla Mujeres', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }
        if (!$playaDelCarmen) {
            $playaDelCarmen = City::create(['name' => 'Playa del Carmen', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }
        if (!$tulum) {
            $tulum = City::create(['name' => 'Tulum', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }
        if (!$akumal) {
            $akumal = City::create(['name' => 'Akumal', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }

        // Create zones if they don't exist
        $cancunCityZone = Zone::firstOrCreate(
            ['name' => 'Cancun City', 'city_id' => $cancun->id],
            ['active' => true]
        );
        
        $puntaCancunZone = Zone::firstOrCreate(
            ['name' => 'Punta Cancun', 'city_id' => $cancun->id],
            ['active' => true]
        );
        
        $islaMujeresZone = Zone::firstOrCreate(
            ['name' => 'Puerto Juarez', 'city_id' => $islaMujeres->id],
            ['active' => true]
        );
        
        $playaZone = Zone::firstOrCreate(
            ['name' => 'Playa del Carmen', 'city_id' => $playaDelCarmen->id], 
            ['active' => true]
        );
        
        $tulumZone = Zone::firstOrCreate(
            ['name' => 'Tulum', 'city_id' => $tulum->id],
            ['active' => true]
        );
        
        $tulumHotelZone = Zone::firstOrCreate(
            ['name' => 'Tulum Hotel Zone', 'city_id' => $tulum->id],
            ['active' => true]
        );
        
        $akumalZone = Zone::firstOrCreate(
            ['name' => 'Akumal', 'city_id' => $akumal->id],
            ['active' => true]
        );

        $locations = [
            // Cancun Hotels
            ['id' => 5560, 'name' => 'Beach Palace Cancun', 'address' => 'Blvd. Kukulcan Km 11.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 3940, 'name' => 'Breathless Cancun Soul Resort & Spa', 'address' => 'Blvd. Kukulcan Km 3.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 3839, 'name' => 'Grand Park Royal Cancún', 'address' => 'Blvd. Kukulcan Km 10.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 3838, 'name' => 'Park Royal Beach Cancún', 'address' => 'Blvd. Kukulcan Km 12.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 2317, 'name' => 'Adhara Hacienda Cancun', 'address' => 'Av. Nader 1, Centro, Cancun', 'zone_id' => $cancunCityZone->id, 'type' => 'H'],
            ['id' => 2318, 'name' => 'Agavero Hostel Cancun', 'address' => 'Calle Tulipanes 35, Centro, Cancun', 'zone_id' => $cancunCityZone->id, 'type' => 'H'],
            ['id' => 2445, 'name' => 'Airbnb Cancun', 'address' => 'Various locations, Cancun', 'zone_id' => $cancunCityZone->id, 'type' => 'H'],
            ['id' => 5362, 'name' => 'All Inclusive Bed, Beach & Fun Cancun', 'address' => 'Blvd. Kukulcan Km 13.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 1354, 'name' => 'Aloft Cancun', 'address' => 'Blvd. Kukulcan Km 8.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 424, 'name' => 'Krystal Cancun', 'address' => 'Blvd. Kukulcan Km 9, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 579, 'name' => 'The Ritz Carlton Cancun', 'address' => 'Retorno del Rey 36, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 1345, 'name' => 'Hard Rock Hotel Cancun', 'address' => 'Blvd. Kukulcan Km 14.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 1359, 'name' => 'Hyatt Zilara Cancun', 'address' => 'Blvd. Kukulcan Km 17.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 1475, 'name' => 'Hyatt Ziva Cancun', 'address' => 'Blvd. Kukulcan Km 16.5, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            ['id' => 2098, 'name' => 'Royalton Suites Cancun', 'address' => 'Blvd. Kukulcan Km 18, Zona Hotelera, Cancun', 'zone_id' => $puntaCancunZone->id, 'type' => 'H'],
            
            // Bus stations
            ['id' => 5702, 'name' => 'ADO Cancun Centro', 'address' => 'Av. Tulum 200, Centro, Cancun', 'zone_id' => $cancunCityZone->id, 'type' => 'B'],
            
            // Isla Mujeres
            ['id' => 1356, 'name' => 'All Ritmo Cancun Resort and Waterpark', 'address' => 'Punta Sam, Isla Mujeres', 'zone_id' => $islaMujeresZone->id, 'type' => 'H'],
            
            // Playa del Carmen
            ['id' => 2117, 'name' => 'The Reef 28', 'address' => '28th Street, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'H'],
            ['id' => 1626, 'name' => 'Hilton Playa del Carmen', 'address' => 'Av. Constituyentes 1, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'H'],
            ['id' => 1552, 'name' => 'Ocean Riviera Paradise', 'address' => 'Carretera Chetumal - Puerto Juárez Km 309, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'H'],
            ['id' => 1436, 'name' => 'Grand Hyatt Playa del Carmen', 'address' => 'Av. Constituyentes 1, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'H'],
            ['id' => 1287, 'name' => 'Occidental Xcaret', 'address' => 'Carretera Chetumal Puerto Juárez Km 282, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'H'],
            ['id' => 909, 'name' => 'Playa del Carmen Ferry', 'address' => 'Av. Rafael E. Melgar, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'F'],
            ['id' => 785, 'name' => 'Ferry to Cozumel', 'address' => 'Terminal Marítima, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'F'],
            ['id' => 782, 'name' => 'Ferry a Cozumel', 'address' => 'Muelle Fiscal, Playa del Carmen', 'zone_id' => $playaZone->id, 'type' => 'F'],
            
            // Tulum
            ['id' => 3186, 'name' => 'Copal Tulum', 'address' => 'Carretera Tulum-Boca Paila Km 8.2, Tulum', 'zone_id' => $tulumHotelZone->id, 'type' => 'H'],
            ['id' => 3731, 'name' => 'Aldea Zama', 'address' => 'Aldea Zama, Tulum', 'zone_id' => $tulumZone->id, 'type' => 'H'],
            ['id' => 1210, 'name' => 'Dreams Tulum Resort and Spa', 'address' => 'Carretera Tulum-Boca Paila Km 7, Tulum', 'zone_id' => $tulumHotelZone->id, 'type' => 'H'],
            ['id' => 3726, 'name' => 'Selina Tulum', 'address' => 'Carretera Tulum-Boca Paila Km 7.5, Tulum', 'zone_id' => $tulumHotelZone->id, 'type' => 'H'],
            ['id' => 1902, 'name' => 'Hotelito Azul Tulum', 'address' => 'Carretera Tulum-Boca Paila Km 3.5, Tulum', 'zone_id' => $tulumHotelZone->id, 'type' => 'H'],
            ['id' => 3708, 'name' => 'Papaya Playa Project', 'address' => 'Carretera Tulum-Boca Paila Km 4.5, Tulum', 'zone_id' => $tulumHotelZone->id, 'type' => 'H'],
            
            // Akumal
            ['id' => 2288, 'name' => 'UNICO Hotel Riviera Maya', 'address' => 'Carretera Cancun Tulum 307 Km 95, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 2281, 'name' => 'Sunscape Akumal Beach Resort & Spa', 'address' => 'Carretera Cancun Tulum Km 104, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 2278, 'name' => 'Secrets Akumal', 'address' => 'Carretera Cancun Tulum Km 103.5, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 2204, 'name' => 'Grand Palladium White Sand Resort And Spa', 'address' => 'Carretera Cancun Tulum Km 105, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 2151, 'name' => 'Trs Yucatan Hotel', 'address' => 'Carretera Cancun Tulum Km 106, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 2046, 'name' => 'Sunscape Akumal Beach Resorts And Spa', 'address' => 'Carretera Cancun Tulum Km 104.5, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H'],
            ['id' => 1463, 'name' => 'Secrets Akumal Riviera Maya', 'address' => 'Carretera Cancun Tulum Km 103, Akumal', 'zone_id' => $akumalZone->id, 'type' => 'H']
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['id' => $location['id']],
                [
                    'name' => $location['name'],
                    'address' => $location['address'],
                    'zone_id' => $location['zone_id'],
                    'type' => $location['type'],
                    'active' => true
                ]
            );
        }
    }
}
