<?php

return [
    // General API responses
    'success' => 'Éxito',
    'error' => 'Ha ocurrido un error',
    'validation_failed' => 'La validación ha fallado',
    'not_found' => 'Recurso no encontrado',
    'internal_server_error' => 'Error interno del servidor',
    'unauthorized' => 'Acceso no autorizado',
    'forbidden' => 'Acceso prohibido',
    'too_many_requests' => 'Demasiadas solicitudes',

    // Resource operations
    'created' => 'Recurso creado exitosamente',
    'updated' => 'Recurso actualizado exitosamente',
    'deleted' => 'Recurso eliminado exitosamente',
    'retrieved' => 'Recurso obtenido exitosamente',

    // Specific resources
    'resources' => [
        'quote' => [
            'created' => 'Cotización creada exitosamente',
            'updated' => 'Cotización actualizada exitosamente',
            'not_found' => 'Cotización no encontrada',
            'retrieved' => 'Cotización obtenida exitosamente',
            'calculated' => 'Cotización calculada exitosamente',
            'invalid_parameters' => 'Parámetros inválidos para el cálculo de cotización',
            'no_routes_found' => 'No se encontraron rutas para las ubicaciones especificadas',
            'rate_not_available' => 'Tarifa no disponible para esta ruta',
        ],
        'rate' => [
            'created' => 'Tarifa creada exitosamente',
            'updated' => 'Tarifa actualizada exitosamente',
            'not_found' => 'Tarifa no encontrada',
            'retrieved' => 'Tarifa obtenida exitosamente',
            'deleted' => 'Tarifa eliminada exitosamente',
            'invalid_zone' => 'Zona inválida especificada',
            'overlapping_zones' => 'Las zonas de tarifas no pueden superponerse',
        ],
        'location' => [
            'created' => 'Ubicación creada exitosamente',
            'updated' => 'Ubicación actualizada exitosamente',
            'not_found' => 'Ubicación no encontrada',
            'retrieved' => 'Ubicación obtenida exitosamente',
            'deleted' => 'Ubicación eliminada exitosamente',
            'invalid_coordinates' => 'Coordenadas inválidas proporcionadas',
        ],
        'zone' => [
            'created' => 'Zona creada exitosamente',
            'updated' => 'Zona actualizada exitosamente',
            'not_found' => 'Zona no encontrada',
            'retrieved' => 'Zona obtenida exitosamente',
            'deleted' => 'Zona eliminada exitosamente',
            'invalid_geometry' => 'Geometría de zona inválida',
        ],
        'vehicle_type' => [
            'created' => 'Tipo de vehículo creado exitosamente',
            'updated' => 'Tipo de vehículo actualizado exitosamente',
            'not_found' => 'Tipo de vehículo no encontrado',
            'retrieved' => 'Tipo de vehículo obtenido exitosamente',
            'deleted' => 'Tipo de vehículo eliminado exitosamente',
        ],
        'city' => [
            'created' => 'Ciudad creada exitosamente',
            'updated' => 'Ciudad actualizada exitosamente',
            'not_found' => 'Ciudad no encontrada',
            'retrieved' => 'Ciudad obtenida exitosamente',
            'deleted' => 'Ciudad eliminada exitosamente',
        ],
    ],

    // Data validation messages
    'validation' => [
        'required' => 'El campo :attribute es obligatorio',
        'email' => 'El :attribute debe ser una dirección de correo válida',
        'unique' => 'El :attribute ya ha sido tomado',
        'min' => 'El :attribute debe tener al menos :min caracteres',
        'max' => 'El :attribute no puede tener más de :max caracteres',
        'numeric' => 'El :attribute debe ser un número',
        'integer' => 'El :attribute debe ser un entero',
        'boolean' => 'El campo :attribute debe ser verdadero o falso',
        'date' => 'El :attribute no es una fecha válida',
        'in' => 'El :attribute seleccionado es inválido',
        'exists' => 'El :attribute seleccionado no existe',
        'coordinates' => 'El :attribute debe ser coordenadas válidas',
        'geometry' => 'El :attribute debe ser geometría válida',
    ],

    // Business logic messages
    'business' => [
        'invalid_route' => 'Ruta inválida especificada',
        'distance_calculation_failed' => 'Falló el cálculo de distancia',
        'no_available_vehicles' => 'No hay vehículos disponibles para esta ruta',
        'price_calculation_error' => 'Error calculando el precio',
        'zone_overlap_detected' => 'Superposición de zonas detectada',
        'location_outside_service_area' => 'La ubicación está fuera del área de servicio',
    ],

    // Pagination
    'pagination' => [
        'showing' => 'Mostrando :from a :to de :total resultados',
        'no_results' => 'No se encontraron resultados',
        'per_page_limit' => 'Máximo :limit elementos por página',
    ],

    // Cache and performance
    'cache' => [
        'cleared' => 'Caché limpiado exitosamente',
        'hit' => 'Datos obtenidos del caché',
        'miss' => 'Datos no encontrados en caché',
    ],
];