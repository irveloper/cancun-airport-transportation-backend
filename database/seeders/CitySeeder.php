<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Cancun', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Puerto Morelos', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Isla Mujeres', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Playa del Carmen', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Tulum', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Akumal', 'state' => 'Quintana Roo', 'country' => 'Mexico'],
            ['name' => 'Cozumel', 'state' => 'Quintana Roo', 'country' => 'Mexico']
        ];

        foreach ($cities as $cityData) {
            City::updateOrCreate(
                ['name' => $cityData['name']],
                [
                    'name' => $cityData['name'],
                    'state' => $cityData['state'],
                    'country' => $cityData['country'],
                    'active' => true
                ]
            );
        }
    }
}
