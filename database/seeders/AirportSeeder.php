<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cancun = City::where('name', 'Cancun')->first();
        $playaDelCarmen = City::where('name', 'Playa del Carmen')->first();
        $cozumel = City::where('name', 'Cozumel')->first();
        $tulum = City::where('name', 'Tulum')->first();

        if (!$cancun) {
            $cancun = City::create([
                'name' => 'Cancun',
                'state' => 'Quintana Roo',
                'country' => 'Mexico'
            ]);
        }

        if (!$playaDelCarmen) {
            $playaDelCarmen = City::create([
                'name' => 'Playa del Carmen', 
                'state' => 'Quintana Roo',
                'country' => 'Mexico'
            ]);
        }

        if (!$cozumel) {
            $cozumel = City::create([
                'name' => 'Cozumel',
                'state' => 'Quintana Roo', 
                'country' => 'Mexico'
            ]);
        }

        if (!$tulum) {
            $tulum = City::create([
                'name' => 'Tulum',
                'state' => 'Quintana Roo',
                'country' => 'Mexico'
            ]);
        }

        $airports = [
            [
                'id' => 1551,
                'name' => 'Cancun Airport (CUN)',
                'code' => 'CUN',
                'city_id' => $cancun->id,
            ],
            [
                'id' => 1552,
                'name' => 'Cozumel Airport (CZM)',
                'code' => 'CZM', 
                'city_id' => $cozumel->id,
            ],
            [
                'id' => 1553,
                'name' => 'Playa del Carmen Airport',
                'code' => 'PCM',
                'city_id' => $playaDelCarmen->id,
            ]
        ];

        foreach ($airports as $airportData) {
            Airport::updateOrCreate(
                ['id' => $airportData['id']],
                $airportData
            );
        }
    }
}
