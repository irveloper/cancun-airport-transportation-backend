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
            ['name' => 'Amatista Estetica Dental Cancun'],
            ['name' => 'Cancun City'],
            ['name' => 'Cancun Country Club'],
            ['name' => 'Club Internacional Cancun'],
            ['name' => 'Coco Bongo Cancun'],
            ['name' => 'Costco Wholesale Cancun'],
            ['name' => 'El Tinto Golf Course Cancun'],
            ['name' => 'Ilios | Greek restaurant in Cancun'],
            ['name' => 'Krispy Cancún zona hotelera'],
            ['name' => 'Marina Puerto Cancún'],
            ['name' => 'Navíos Restaurante Cancun Zona Hotelera'],
            ['name' => 'Nicoletta | Italian restaurant in Cancun'],
            ['name' => 'Playa Delfines Cancun'],
            ['name' => 'Playa Langosta Cancun'],
            ['name' => 'Plaza Caracol Cancun'],
            ['name' => 'Plaza La Isla Cancun'],
            ['name' => 'Plazas Outlet Cancun'],
            ['name' => 'Porfirios Cancun Restaurante'],
            ['name' => 'Punta Cancun'],
            ['name' => 'Residencial Campestre Cancun'],
            ['name' => 'WOHA Puerto Cancun'],
        ];

        foreach ($zones as $zone) {
            Zone::firstOrCreate(
                ['name' => $zone['name'], 'city_id' => $cancun->id],
                [
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
            ['name' => 'Puerto Juarez', 'city_id' => $islaMujeres->id],
            ['name' => 'Playa del Carmen', 'city_id' => $playaDelCarmen->id],
        ];

        foreach ($additionalZones as $zone) {
            Zone::firstOrCreate(
                ['name' => $zone['name'], 'city_id' => $zone['city_id']],
                [
                    'active' => true
                ]
            );
        }
    }
}
