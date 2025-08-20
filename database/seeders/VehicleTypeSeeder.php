<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;
use App\Models\ServiceFeature;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear los tipos de vehículos
        $standardPrivate = VehicleType::create([
            'name' => 'standard private',
            'code' => 'ES',
            'image' => 'van.png',
            'max_units' => 44,
            'max_pax' => 7,
            'travel_time' => '1 hour and 30 minutes',
            'video_url' => 'http://www.youtube.com/embed/_zPlr-o-YEQ',
            'frame' => 'van/iframe.html',
            'active' => true,
        ]);

        $crafter = VehicleType::create([
            'name' => 'CRAFTER',
            'code' => 'ES',
            'image' => 'crafter.png',
            'max_units' => 6,
            'max_pax' => 17,
            'travel_time' => '1 hour and 30 minutes',
            'video_url' => 'http://www.youtube.com/embed/ZO-jQjEdK9w',
            'frame' => 'crafter/iframe.html',
            'active' => true,
        ]);

        $vipPrivate = VehicleType::create([
            'name' => 'vip private',
            'code' => 'VP',
            'image' => 'suburban.png',
            'max_units' => 28,
            'max_pax' => 5,
            'travel_time' => '1 hour and 30 minutes',
            'video_url' => 'http://www.youtube.com/embed/g_w7GJBfiYc',
            'frame' => 'suburban/iframe.html',
            'active' => true,
        ]);

        $economicalLimo = VehicleType::create([
            'name' => 'Economical Limo',
            'code' => 'LS',
            'image' => 'limo-sienna.png',
            'max_units' => 2,
            'max_pax' => 8,
            'travel_time' => '1 hour and 30 minutes',
            'video_url' => 'http://www.youtube.com/embed/_zPlr-o-YEQ',
            'frame' => 'sienna/iframe.html',
            'active' => true,
        ]);

        $limousines = VehicleType::create([
            'name' => 'limousines',
            'code' => 'LS',
            'image' => 'limo-tundra.png',
            'max_units' => 2,
            'max_pax' => 8,
            'travel_time' => '1 hour and 30 minutes',
            'video_url' => 'http://www.youtube.com/embed/_zPlr-o-YEQ',
            'frame' => 'tundra/iframe.html',
            'active' => true,
        ]);

        // Asignar features a cada tipo de vehículo
        $standardPrivate->serviceFeatures()->attach([1, 2, 3, 4, 5, 6, 7, 8]); // Standard features
        $crafter->serviceFeatures()->attach([1, 2, 3, 5, 6, 7, 8]); // Similar to standard but larger
        $vipPrivate->serviceFeatures()->attach([1, 2, 3, 4, 9, 5, 8]); // VIP with towels and water
        $economicalLimo->serviceFeatures()->attach([1, 10, 11, 13, 15, 14, 8]); // Limo features with wine but no car seats
        $limousines->serviceFeatures()->attach([1, 10, 12, 13, 6, 14, 2, 8]); // Premium limo with sparkling wine
    }
}