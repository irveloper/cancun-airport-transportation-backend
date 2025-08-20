<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $cancun = City::where('name', 'Cancun')->first();

        $zones = [
            ['id' => 5683, 'name' => 'Amatista Estetica Dental Cancun'],
            ['id' => 1447, 'name' => 'Cancun City'],
            ['id' => 5787, 'name' => 'Cancun Country Club'],
            ['id' => 223, 'name' => 'Club Internacional Cancun'],
            ['id' => 5186, 'name' => 'Coco Bongo Cancun'],
            ['id' => 5352, 'name' => 'Costco Wholesale Cancun'],
            ['id' => 5331, 'name' => 'El Tinto Golf Course Cancun'],
            ['id' => 5180, 'name' => 'Ilios | Greek restaurant in Cancun'],
            ['id' => 5334, 'name' => 'Krispy Cancún zona hotelera'],
            ['id' => 5770, 'name' => 'Marina Puerto Cancún'],
            ['id' => 5320, 'name' => 'Navíos Restaurante Cancun Zona Hotelera'],
            ['id' => 5447, 'name' => 'Nicoletta | Italian restaurant in Cancun'],
            ['id' => 5332, 'name' => 'Playa Delfines Cancun'],
            ['id' => 5439, 'name' => 'Playa Langosta Cancun'],
            ['id' => 5771, 'name' => 'Plaza Caracol Cancun'],
            ['id' => 5627, 'name' => 'Plaza La Isla Cancun'],
            ['id' => 5353, 'name' => 'Plazas Outlet Cancun'],
            ['id' => 3808, 'name' => 'Porfirios Cancun Restaurante'],
            ['id' => 507, 'name' => 'Punta Cancun'],
            ['id' => 5655, 'name' => 'Residencial Campestre Cancun'],
            ['id' => 5544, 'name' => 'WOHA Puerto Cancun'],
        ];

        foreach ($zones as $zone) {
            Zone::updateOrCreate(
                ['id' => $zone['id']],
                [
                    'name' => $zone['name'],
                    'city_id' => $cancun->id,
                    'active' => true
                ]
            );
        }

        // Add additional zones for other cities
        $islaMujeres = City::where('name', 'Isla Mujeres')->first();
        $playaDelCarmen = City::where('name', 'Playa del Carmen')->first();
        
        if (!$islaMujeres) {
            $islaMujeres = City::create(['name' => 'Isla Mujeres', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }
        if (!$playaDelCarmen) {
            $playaDelCarmen = City::create(['name' => 'Playa del Carmen', 'state' => 'Quintana Roo', 'country' => 'Mexico']);
        }

        $additionalZones = [
            ['id' => 1589, 'name' => 'Puerto Juarez', 'city_id' => $islaMujeres->id],
            ['id' => 908, 'name' => 'Playa del Carmen', 'city_id' => $playaDelCarmen->id],
        ];

        foreach ($additionalZones as $zone) {
            Zone::updateOrCreate(
                ['id' => $zone['id']],
                [
                    'name' => $zone['name'],
                    'city_id' => $zone['city_id'],
                    'active' => true
                ]
            );
        }
    }
}
