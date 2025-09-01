<?php

return [
    // General API responses
    'success' => 'Success',
    'error' => 'An error occurred',
    'validation_failed' => 'Validation failed',
    'not_found' => 'Resource not found',
    'internal_server_error' => 'Internal server error',
    'unauthorized' => 'Unauthorized access',
    'forbidden' => 'Forbidden access',
    'too_many_requests' => 'Too many requests',

    // Resource operations
    'created' => 'Resource created successfully',
    'updated' => 'Resource updated successfully',
    'deleted' => 'Resource deleted successfully',
    'retrieved' => 'Resource retrieved successfully',

    // Specific resources
    'resources' => [
        'quote' => [
            'created' => 'Quote created successfully',
            'updated' => 'Quote updated successfully',
            'not_found' => 'Quote not found',
            'retrieved' => 'Quote retrieved successfully',
            'calculated' => 'Quote calculated successfully',
            'invalid_parameters' => 'Invalid parameters for quote calculation',
            'no_routes_found' => 'No routes found for the specified locations',
            'rate_not_available' => 'Rate not available for this route',
        ],
        'rate' => [
            'created' => 'Rate created successfully',
            'updated' => 'Rate updated successfully',
            'not_found' => 'Rate not found',
            'retrieved' => 'Rate retrieved successfully',
            'deleted' => 'Rate deleted successfully',
            'invalid_zone' => 'Invalid zone specified',
            'overlapping_zones' => 'Rate zones cannot overlap',
        ],
        'location' => [
            'created' => 'Location created successfully',
            'updated' => 'Location updated successfully',
            'not_found' => 'Location not found',
            'retrieved' => 'Location retrieved successfully',
            'deleted' => 'Location deleted successfully',
            'invalid_coordinates' => 'Invalid coordinates provided',
        ],
        'zone' => [
            'created' => 'Zone created successfully',
            'updated' => 'Zone updated successfully',
            'not_found' => 'Zone not found',
            'retrieved' => 'Zone retrieved successfully',
            'deleted' => 'Zone deleted successfully',
            'invalid_geometry' => 'Invalid zone geometry',
        ],
        'vehicle_type' => [
            'created' => 'Vehicle type created successfully',
            'updated' => 'Vehicle type updated successfully',
            'not_found' => 'Vehicle type not found',
            'retrieved' => 'Vehicle type retrieved successfully',
            'deleted' => 'Vehicle type deleted successfully',
        ],
        'city' => [
            'created' => 'City created successfully',
            'updated' => 'City updated successfully',
            'not_found' => 'City not found',
            'retrieved' => 'City retrieved successfully',
            'deleted' => 'City deleted successfully',
        ],
    ],

    // Data validation messages
    'validation' => [
        'required' => 'The :attribute field is required',
        'email' => 'The :attribute must be a valid email address',
        'unique' => 'The :attribute has already been taken',
        'min' => 'The :attribute must be at least :min characters',
        'max' => 'The :attribute may not be greater than :max characters',
        'numeric' => 'The :attribute must be a number',
        'integer' => 'The :attribute must be an integer',
        'boolean' => 'The :attribute field must be true or false',
        'date' => 'The :attribute is not a valid date',
        'in' => 'The selected :attribute is invalid',
        'exists' => 'The selected :attribute does not exist',
        'coordinates' => 'The :attribute must be valid coordinates',
        'geometry' => 'The :attribute must be valid geometry',
        'service_type_invalid' => 'The service type must be one of: round-trip, one-way, hotel-to-hotel',
        'locations_must_be_different' => 'Pickup and dropoff locations must be different',
        'passenger_count_min' => 'At least 1 passenger is required',
        'passenger_count_max' => 'Maximum 50 passengers allowed',
        'date_future' => 'The date must be today or in the future',
    ],

    // Business logic messages
    'business' => [
        'invalid_route' => 'Invalid route specified',
        'distance_calculation_failed' => 'Failed to calculate distance',
        'no_available_vehicles' => 'No vehicles available for this route',
        'price_calculation_error' => 'Error calculating price',
        'zone_overlap_detected' => 'Zone overlap detected',
        'location_outside_service_area' => 'Location is outside service area',
    ],

    // Pagination
    'pagination' => [
        'showing' => 'Showing :from to :to of :total results',
        'no_results' => 'No results found',
        'per_page_limit' => 'Maximum :limit items per page',
    ],

    // Cache and performance
    'cache' => [
        'cleared' => 'Cache cleared successfully',
        'hit' => 'Data retrieved from cache',
        'miss' => 'Data not found in cache',
    ],
];