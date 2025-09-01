# Zone-Based Rates System Guide

## Overview

The zone-based rates system is designed to solve the scalability problem of managing rates for every location-to-location combination. Instead of creating individual rates for each location pair, you can now define rates between zones, and locations within those zones will automatically inherit the appropriate rates.

## Key Benefits

1. **Scalability**: Instead of managing N² location combinations, you manage zone-to-zone rates
2. **Maintainability**: Update rates for entire zones instead of individual locations
3. **Flexibility**: Still supports location-specific overrides when needed
4. **Performance**: Faster queries and reduced database size

## System Architecture

### Database Structure

The `rates` table now supports two pricing models:

1. **Zone-based pricing**: `from_zone_id` and `to_zone_id`
2. **Location-specific pricing**: `from_location_id` and `to_location_id`

A rate must use either zone-based OR location-specific pricing, but not both.

### Rate Resolution Priority

When looking up rates for a location-to-location route:

1. **Location-specific rates** (highest priority)
2. **Zone-based rates** (fallback)

## API Endpoints

### Quote Generation

```
GET /api/v1/quote?service_type=round-trip&from_location_id=1551&to_location_id=1&pax=2
```

The quote system automatically:
- Finds the zones for both locations
- Looks for location-specific rates first
- Falls back to zone-based rates if no location-specific rates exist
- Returns the appropriate pricing

### Rate Management

#### List Rates
```
GET /api/v1/rates
GET /api/v1/rates?rate_type=zone
GET /api/v1/rates?rate_type=location
GET /api/v1/rates?from_zone_id=1&to_zone_id=2
```

#### Create Zone-Based Rate
```json
POST /api/v1/rates
{
    "service_type_id": 1,
    "vehicle_type_id": 2,
    "from_zone_id": 1,
    "to_zone_id": 2,
    "cost_vehicle_one_way": 82.00,
    "total_one_way": 82,
    "cost_vehicle_round_trip": 150.00,
    "total_round_trip": 150,
    "num_vehicles": 1,
    "available": true
}
```

#### Create Location-Specific Rate (Override)
```json
POST /api/v1/rates
{
    "service_type_id": 1,
    "vehicle_type_id": 2,
    "from_location_id": 1551,
    "to_location_id": 1,
    "cost_vehicle_one_way": 85.00,
    "total_one_way": 85,
    "cost_vehicle_round_trip": 155.00,
    "total_round_trip": 155,
    "num_vehicles": 1,
    "available": true
}
```

#### Get Route Rates
```
GET /api/v1/rates/route?service_type_id=1&from_location_id=1551&to_location_id=1
```

#### Get Zone Rates
```
GET /api/v1/rates/zone?service_type_id=1&from_zone_id=1&to_zone_id=2
```

## Implementation Examples

### Setting Up Zone-Based Rates

1. **Create Zones**:
```php
$cancunZone = Zone::create([
    'name' => 'Cancun',
    'city_id' => 1,
    'active' => true,
    'description' => 'Cancun zone including airport and hotel area'
]);

$akumalZone = Zone::create([
    'name' => 'Akumal',
    'city_id' => 1,
    'active' => true,
    'description' => 'Akumal zone'
]);
```

2. **Assign Locations to Zones**:
```php
$airportLocation = Location::create([
    'name' => 'CANCUN AIRPORT',
    'zone_id' => $cancunZone->id,
    'type' => 'A', // Airport
    'active' => true
]);

$hotelLocation = Location::create([
    'name' => 'AKUMAL HOTEL',
    'zone_id' => $akumalZone->id,
    'type' => 'H', // Hotel
    'active' => true
]);
```

3. **Create Zone-Based Rate**:
```php
Rate::create([
    'service_type_id' => $roundTripService->id,
    'vehicle_type_id' => $standardVan->id,
    'from_zone_id' => $cancunZone->id,
    'to_zone_id' => $akumalZone->id,
    'cost_vehicle_one_way' => 82.00,
    'total_one_way' => 82,
    'cost_vehicle_round_trip' => 150.00,
    'total_round_trip' => 150,
    'num_vehicles' => 1,
    'available' => true,
]);
```

### Finding Rates Programmatically

```php
// Find rates for a specific route
$rates = Rate::findForRoute(
    $serviceTypeId,
    $fromLocationId,
    $toLocationId,
    $date
);

// Check if a rate is zone-based
if ($rate->isZoneBased()) {
    echo "This is a zone-based rate";
}

// Check if a rate is location-specific
if ($rate->isLocationSpecific()) {
    echo "This is a location-specific rate";
}
```

## Migration Strategy

### From Location-Based to Zone-Based

1. **Run the migration**:
```bash
php artisan migrate
```

2. **Create zones** for your existing locations

3. **Update locations** to belong to zones

4. **Create zone-based rates** for common routes

5. **Keep location-specific rates** for special cases

### Data Migration Example

```php
// Create zones based on existing location patterns
$zones = Location::select('city_id')
    ->distinct()
    ->get()
    ->map(function ($city) {
        return Zone::create([
            'name' => $city->city->name,
            'city_id' => $city->city_id,
            'active' => true
        ]);
    });

// Assign locations to zones
Location::chunk(100, function ($locations) use ($zones) {
    foreach ($locations as $location) {
        $zone = $zones->where('city_id', $location->city_id)->first();
        if ($zone) {
            $location->update(['zone_id' => $zone->id]);
        }
    }
});
```

## Best Practices

### Zone Design

1. **Logical grouping**: Group locations by geographic proximity or service area
2. **Size balance**: Don't make zones too large or too small
3. **Common routes**: Consider which locations are frequently traveled between

### Rate Management

1. **Start with zones**: Create zone-based rates for common routes
2. **Use overrides sparingly**: Only create location-specific rates when necessary
3. **Regular review**: Periodically review and consolidate similar rates

### Performance

1. **Indexes**: The system includes indexes for optimal query performance
2. **Caching**: Consider caching frequently accessed rates
3. **Batch operations**: Use bulk operations for rate updates

## Troubleshooting

### Common Issues

1. **No rates found**: Check if zones exist and are properly assigned
2. **Validation errors**: Ensure either zone-based OR location-specific pricing is provided
3. **Performance issues**: Check if proper indexes are in place

### Debug Queries

```php
// Check zone assignments
Location::with('zone')->get()->each(function ($location) {
    echo "Location: {$location->name}, Zone: {$location->zone->name}\n";
});

// Check available rates
Rate::with(['fromZone', 'toZone', 'fromLocation', 'toLocation'])
    ->get()
    ->each(function ($rate) {
        if ($rate->isZoneBased()) {
            echo "Zone rate: {$rate->fromZone->name} -> {$rate->toZone->name}\n";
        } else {
            echo "Location rate: {$rate->fromLocation->name} -> {$rate->toLocation->name}\n";
        }
    });
```

## API Response Examples

### Zone-Based Rate Response
```json
{
    "id": 1,
    "service_type": {
        "id": 1,
        "name": "Round Trip",
        "code": "RT"
    },
    "vehicle_type": {
        "id": 2,
        "name": "Standard Van",
        "code": "ES"
    },
    "from_zone": {
        "id": 1,
        "name": "Cancun"
    },
    "to_zone": {
        "id": 2,
        "name": "Akumal"
    },
    "pricing_type": "zone",
    "cost_vehicle_one_way": "82.00",
    "total_one_way": 82,
    "cost_vehicle_round_trip": "150.00",
    "total_round_trip": 150,
    "available": true
}
```

### Quote Response
```json
{
    "exchangeDollar": "1.000000",
    "exchangeMXN": "0.050251",
    "currency": "usd",
    "fromHotelId": "1551",
    "toHotelId": "1",
    "fromHotel": "CANCUN AIRPORT",
    "toHotel": "AKUMAL",
    "toDestination": "akumal",
    "toDestinationId": 1,
    "fromDestination": "cancun",
    "fromDestinationId": 4,
    "serviceTypeTPV": "service_airport",
    "prices": [
        {
            "id": 2,
            "name": "standard private",
            "pic": "van.png",
            "type": "ES",
            "costVehicleOW": "82.00",
            "totalOW": 82,
            "costVehicleRT": "150.00",
            "totalRT": 150,
            "available": 1
        }
    ]
}
```

This system provides a scalable, maintainable solution for managing transportation rates while maintaining the flexibility to handle special cases when needed.

