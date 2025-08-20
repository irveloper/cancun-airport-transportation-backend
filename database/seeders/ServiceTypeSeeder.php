<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceType;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            [
                'name' => 'Round Trip',
                'code' => 'RT',
                'tpv_type' => 'service_airport',
                'description' => 'Airport to hotel and hotel to airport service',
                'active' => true,
            ],
            [
                'name' => 'One Way',
                'code' => 'OW',
                'tpv_type' => 'service_airport',
                'description' => 'One way service from airport to hotel or hotel to airport',
                'active' => true,
            ],
            [
                'name' => 'Hotel to Hotel',
                'code' => 'HTH',
                'tpv_type' => 'service_hotel_hotel',
                'description' => 'Service between hotels or locations',
                'active' => true,
            ],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}