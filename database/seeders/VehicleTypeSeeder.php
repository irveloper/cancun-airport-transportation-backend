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
            'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/economic_yjkomz.webp',
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
            'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/crafter_c2mvxn.webp',
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
            'image' => 'https://res.cloudinary.com/codepom-mvp/image/upload/v1757428489/five-stars/services/luxury_j4wmyt.webp',
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

        // Get features by sort_order to avoid hardcoded IDs
        $features = ServiceFeature::whereIn('sort_order', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])->get()->keyBy('sort_order');

        // Asignar features a cada tipo de vehículo
        $standardPrivate->serviceFeatures()->attach($features->only([1, 2, 3, 4, 5, 6, 7, 8])->pluck('id')); // Standard features
        $crafter->serviceFeatures()->attach($features->only([1, 2, 3, 5, 6, 7, 8])->pluck('id')); // Similar to standard but larger
        $vipPrivate->serviceFeatures()->attach($features->only([1, 2, 3, 4, 9, 5, 8])->pluck('id')); // VIP with towels and water
        $economicalLimo->serviceFeatures()->attach($features->only([1, 10, 11, 13, 15, 14, 8])->pluck('id')); // Limo features with wine but no car seats
        $limousines->serviceFeatures()->attach($features->only([1, 10, 12, 13, 6, 14, 2, 8])->pluck('id')); // Premium limo with sparkling wine
    }
}
