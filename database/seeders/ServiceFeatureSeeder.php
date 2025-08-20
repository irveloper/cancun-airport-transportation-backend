<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceFeature;

class ServiceFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name_en' => 'Vehicle with A/C',
                'name_es' => 'Vehiculo con A/C',
                'description_en' => 'Air conditioned vehicle for comfortable travel',
                'description_es' => 'Vehículo con aire acondicionado para viajes cómodos',
                'icon' => 'ac-unit',
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Travel insurances',
                'name_es' => 'Seguros de Viaje',
                'description_en' => 'Comprehensive travel insurance coverage',
                'description_es' => 'Cobertura completa de seguros de viaje',
                'icon' => 'shield',
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Meet & Greet at the airport',
                'name_es' => 'Personal en aeropuerto',
                'description_en' => 'Personal assistance at airport arrival',
                'description_es' => 'Asistencia personal en llegada al aeropuerto',
                'icon' => 'person-pin',
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Non-Stops, Direct Service',
                'name_es' => 'Servicio Privado',
                'description_en' => 'Direct service without stops',
                'description_es' => 'Servicio directo sin paradas',
                'icon' => 'direct',
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Baby Car Seat depends on availability',
                'name_es' => 'Silla de Bebé según disponibilidad',
                'description_en' => 'Child car seats available upon request',
                'description_es' => 'Sillas para niños disponibles bajo petición',
                'icon' => 'child-care',
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Flight monitoring',
                'name_es' => 'Monitoreo de Vuelos',
                'description_en' => 'Real-time flight tracking and monitoring',
                'description_es' => 'Seguimiento y monitoreo de vuelos en tiempo real',
                'icon' => 'flight',
                'sort_order' => 6,
            ],
            [
                'name_en' => 'No fees for flight changes',
                'name_es' => 'Sin cargos extras',
                'description_en' => 'No additional charges for flight changes',
                'description_es' => 'Sin cargos adicionales por cambios de vuelo',
                'icon' => 'money-off',
                'sort_order' => 7,
            ],
            [
                'name_en' => 'Professional driver',
                'name_es' => 'Conductor Profesional',
                'description_en' => 'Experienced and professional drivers',
                'description_es' => 'Conductores experimentados y profesionales',
                'icon' => 'person',
                'sort_order' => 8,
            ],
            [
                'name_en' => 'Fresh Towels and Water',
                'name_es' => 'Aguas y toallas refrescantes',
                'description_en' => 'Complimentary fresh towels and water bottles',
                'description_es' => 'Toallas frescas y botellas de agua de cortesía',
                'icon' => 'water-drop',
                'sort_order' => 9,
            ],
            [
                'name_en' => 'Free WIFI included',
                'name_es' => 'WIFI incluido',
                'description_en' => 'Complimentary wireless internet access',
                'description_es' => 'Acceso gratuito a internet inalámbrico',
                'icon' => 'wifi',
                'sort_order' => 10,
            ],
            [
                'name_en' => '1 Bottle of White Wine',
                'name_es' => '1 Botella de Vino Blanco',
                'description_en' => 'Complimentary bottle of white wine',
                'description_es' => 'Botella de vino blanco de cortesía',
                'icon' => 'wine-bar',
                'sort_order' => 11,
            ],
            [
                'name_en' => '1 Bottle of Sparkling Wine',
                'name_es' => '1 Botella de Vino Espumoso',
                'description_en' => 'Complimentary bottle of sparkling wine',
                'description_es' => 'Botella de vino espumoso de cortesía',
                'icon' => 'wine-bar',
                'sort_order' => 12,
            ],
            [
                'name_en' => 'Child Car Seat NOT AVAILABLE',
                'name_es' => 'Silla de Bebé No Disponible',
                'description_en' => 'Child car seats are not available for this service',
                'description_es' => 'Las sillas para niños no están disponibles para este servicio',
                'icon' => 'block',
                'sort_order' => 13,
            ],
            [
                'name_en' => 'Amenities included',
                'name_es' => 'Amenidades Incluidas',
                'description_en' => 'Various luxury amenities included',
                'description_es' => 'Varias amenidades de lujo incluidas',
                'icon' => 'star',
                'sort_order' => 14,
            ],
            [
                'name_en' => '6 MEDIUM BAGS MAX.',
                'name_es' => '6 MALETAS MEDIANAS MAX.',
                'description_en' => 'Maximum capacity of 6 medium-sized bags',
                'description_es' => 'Capacidad máxima de 6 maletas medianas',
                'icon' => 'luggage',
                'sort_order' => 15,
            ],
        ];

        foreach ($features as $feature) {
            ServiceFeature::create($feature);
        }
    }
}