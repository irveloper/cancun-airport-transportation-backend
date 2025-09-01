<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current version of your API. This is used for versioning
    | and backward compatibility.
    |
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different API endpoints.
    | Limits are defined as [max_attempts, decay_minutes]
    |
    */

    'rate_limits' => [
        'default' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'quote' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
        'autocomplete' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
        ],
        'rates' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
        ],
        'admin' => [
            'max_attempts' => 300,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching strategies for different API endpoints.
    | TTL values are in seconds.
    |
    */

    'caching' => [
        'enabled' => env('API_CACHING_ENABLED', true),
        'default_ttl' => 3600, // 1 hour
        'quote_ttl' => 900, // 15 minutes
        'rates_ttl' => 1800, // 30 minutes
        'locations_ttl' => 3600, // 1 hour
        'service_types_ttl' => 7200, // 2 hours
        'vehicle_types_ttl' => 7200, // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | API Pagination
    |--------------------------------------------------------------------------
    |
    | Configure pagination settings for API responses.
    |
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'min_per_page' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Response Format
    |--------------------------------------------------------------------------
    |
    | Configure the standard response format for all API endpoints.
    |
    */

    'response_format' => [
        'include_timestamp' => true,
        'include_request_id' => true,
        'include_pagination_meta' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | Configure security settings for the API.
    |
    */

    'security' => [
        'enable_rate_limiting' => env('API_RATE_LIMITING_ENABLED', true),
        'enable_input_sanitization' => env('API_INPUT_SANITIZATION_ENABLED', true),
        'enable_cors' => env('API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('API_ALLOWED_ORIGINS', '*')),
        'max_request_size' => env('API_MAX_REQUEST_SIZE', '10MB'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and logging settings.
    |
    */

    'monitoring' => [
        'enable_performance_monitoring' => env('API_PERFORMANCE_MONITORING_ENABLED', true),
        'slow_query_threshold_ms' => env('API_SLOW_QUERY_THRESHOLD_MS', 1000),
        'enable_error_tracking' => env('API_ERROR_TRACKING_ENABLED', true),
        'log_slow_queries' => env('API_LOG_SLOW_QUERIES', true),
        'log_api_requests' => env('API_LOG_REQUESTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    |
    | Configure API documentation settings.
    |
    */

    'documentation' => [
        'enabled' => env('API_DOCUMENTATION_ENABLED', true),
        'title' => env('API_DOCUMENTATION_TITLE', 'FiveStars Transportation API'),
        'version' => env('API_DOCUMENTATION_VERSION', '1.0.0'),
        'description' => env('API_DOCUMENTATION_DESCRIPTION', 'API for transportation booking and rate management'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints Configuration
    |--------------------------------------------------------------------------
    |
    | Configure specific settings for different API endpoints.
    |
    */

    'endpoints' => [
        'quote' => [
            'cache_enabled' => true,
            'cache_ttl' => 900,
            'rate_limit' => 'quote',
            'max_pax' => 50,
            'min_pax' => 1,
        ],
        'rates' => [
            'cache_enabled' => true,
            'cache_ttl' => 1800,
            'rate_limit' => 'rates',
            'max_per_page' => 100,
        ],
        'locations' => [
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'rate_limit' => 'default',
        ],
        'autocomplete' => [
            'cache_enabled' => true,
            'cache_ttl' => 300,
            'rate_limit' => 'autocomplete',
            'max_results' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Query Optimization
    |--------------------------------------------------------------------------
    |
    | Configure database query optimization settings.
    |
    */

    'database' => [
        'enable_query_logging' => env('API_QUERY_LOGGING_ENABLED', false),
        'enable_query_caching' => env('API_QUERY_CACHING_ENABLED', true),
        'max_query_time_ms' => env('API_MAX_QUERY_TIME_MS', 5000),
        'enable_connection_pooling' => env('API_CONNECTION_POOLING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency and Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Configure currency and exchange rate settings.
    |
    */

    'currency' => [
        'default' => env('API_DEFAULT_CURRENCY', 'USD'),
        'supported_currencies' => ['USD', 'MXN', 'EUR'],
        'exchange_rate_cache_ttl' => 3600, // 1 hour
        'exchange_rate_provider' => env('API_EXCHANGE_RATE_PROVIDER', 'manual'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Type Configuration
    |--------------------------------------------------------------------------
    |
    | Configure service type mappings and settings.
    |
    */

    'service_types' => [
        'mappings' => [
            'round-trip' => 'RT',
            'round trip' => 'RT',
            'roundtrip' => 'RT',
            'one-way' => 'OW',
            'one way' => 'OW',
            'oneway' => 'OW',
            'hotel-to-hotel' => 'HTH',
            'hotel to hotel' => 'HTH',
            'hotel_to_hotel' => 'HTH',
        ],
        'default' => 'RT',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zone-Based Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure zone-based pricing settings.
    |
    */

    'zone_pricing' => [
        'enabled' => true,
        'cache_ttl' => 1800, // 30 minutes
        'fallback_to_location_pricing' => true,
        'priority_order' => ['location_specific', 'zone_based'],
    ],

];

