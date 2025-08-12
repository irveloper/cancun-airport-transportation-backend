<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Zone;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ImportInitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'airport' => [],
            'zones' => [
                // paste your zones here (id, name, city)
                // e.g. ["id" => "5683", "name" => "Amatista Estetica Dental Cancun", "city" => "Cancun"],
            ],
            'locations' => [
                // paste your locations keyed by city external ids here
                // e.g. "4" => ["name" => "Cancun", "locations" => [...]]
            ],
        ];

        // 1) Create/find cities from locations keys and names
        $cityByName = [];

        if (!empty($data['locations'])) {
            foreach ($data['locations'] as $extCityId => $cityBlock) {
                $cityName = trim($cityBlock['name']);
                $city = City::firstOrCreate(
                    ['name' => $cityName],
                    ['external_id' => (string) $extCityId]
                );
                $cityByName[$cityName] = $city;
            }
        }

        // Ensure cities that appear only in zones are also created
        foreach ($data['zones'] as $zone) {
            $cityName = trim($zone['city']);
            if (!isset($cityByName[$cityName])) {
                $cityByName[$cityName] = City::firstOrCreate(['name' => $cityName], ['external_id' => null]);
            }
        }

        // 2) Import zones
        foreach ($data['zones'] as $z) {
            $city = $cityByName[trim($z['city'])] ?? null;
            if (!$city) {
                continue;
            }

            Zone::firstOrCreate(
                ['external_id' => (string) $z['id']],
                [
                    'city_id' => $city->id,
                    'name'    => trim($z['name']),
                ]
            );
        }

        // 3) Import airports as locations (type 'A')
        foreach ($data['airport'] as $a) {
            $city = $cityByName[trim($a['city'])] ?? null;
            if (!$city) {
                continue;
            }

            Location::firstOrCreate(
                ['external_id' => (string) $a['id']],
                [
                    'city_id' => $city->id,
                    'zone_id' => null,
                    'name'    => trim($a['name']),
                    'type'    => 'A',
                ]
            );
        }

        // 4) Import city locations (H/B)
        foreach ($data['locations'] as $extCityId => $cityBlock) {
            $cityName = trim($cityBlock['name']);
            $city = $cityByName[$cityName] ?? null;
            if (!$city) {
                continue;
            }

            foreach ($cityBlock['locations'] as $loc) {
                $type = in_array($loc['type'], ['H','B'], true) ? $loc['type'] : 'P';

                Location::firstOrCreate(
                    ['external_id' => (string) $loc['id']],
                    [
                        'city_id' => $city->id,
                        'zone_id' => null,
                        'name'    => trim($loc['name']),
                        'type'    => $type,
                    ]
                );
            }
        }
    }
}
