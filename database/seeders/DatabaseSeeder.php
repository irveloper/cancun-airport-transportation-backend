<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed our location data
        $this->call([
            CitySeeder::class,
            ZoneSeeder::class,
            AirportSeeder::class,
            LocationSeeder::class,
            ServiceTypeSeeder::class,
            ServiceFeatureSeeder::class, // Debe ejecutarse antes de VehicleTypeSeeder
            VehicleTypeSeeder::class,
            RateSeeder::class, // Debe ejecutarse después de tener ubicaciones, servicios y vehículos
        ]);
    }
}
