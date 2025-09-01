# FiveStars API - Complete Testing Guide with Real Parameters

## 📋 Overview

This guide provides comprehensive examples of all API endpoints with **real query parameters, request bodies, and proper usage patterns**. Every endpoint includes actual parameter values that work with the FiveStars transportation API.

## 🔍 Autocomplete Service

### Required Parameters
The autocomplete endpoint requires these parameters for proper functionality:

```http
GET /api/v1/autocomplete?lang={language}&type={service_type}&input={field}&q={search_query}
```

#### Core Parameters

| Parameter | Required | Values | Description |
|-----------|----------|--------|-------------|
| `lang` | Yes | `en`, `es`, `fr` | Language code (2 characters) |
| `type` | Yes | `round-trip`, `arrival`, `departure`, `transfer-one-way`, `transfer-round-trip` | Service type |
| `input` | Yes | `from`, `to` | Which input field is being filled |
| `q` | Optional | Any string | Search query (location name, city, airport code) |
| `from` | Optional | Location ID | Context location ID |
| `start` | Optional | Number | Start offset for results |

#### Real Examples

**Airport Search - Round Trip:**
```http
GET /api/v1/autocomplete?lang=en&type=round-trip&input=from&q=airport
Accept-Language: en-US,en;q=0.9
```

**Hotel Search - Departure Service:**
```http
GET /api/v1/autocomplete?lang=es&type=departure&input=from&q=hotel&start=1
Accept-Language: es-MX,es;q=0.9
```

**Destination Search with Context:**
```http
GET /api/v1/autocomplete?lang=fr&type=arrival&input=to&q=playa&from=1
Accept-Language: fr-FR,fr;q=0.9
```

**Transfer Service - All Suggestions:**
```http
GET /api/v1/autocomplete?lang=en&type=transfer-one-way&input=from&q=can&from=2
```

### Response Structure
```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": {
    "airport": [
      {
        "id": "1",
        "name": "Cancun International Airport",
        "city": "Cancun"
      }
    ],
    "zones": [
      {
        "id": "2",
        "name": "Hotel Zone",
        "city": "Cancun"
      }
    ],
    "locations": {
      "1": {
        "name": "Cancun",
        "locations": [
          {
            "id": "5",
            "name": "Grand Oasis Cancun",
            "type": "H",
            "city": "Cancun"
          }
        ]
      }
    }
  },
  "timestamp": "2025-08-25T10:30:00.000000Z",
  "request_id": "req_12345"
}
```

## 💰 Quote Management

### Quote Calculation Parameters

```http
GET /api/v1/quote?service_type={type}&from_location_id={from}&to_location_id={to}&pax={passengers}&date={date}
```

#### Parameters

| Parameter | Required | Values | Description |
|-----------|----------|--------|-------------|
| `service_type` | Yes | `one-way`, `round-trip`, `hotel-to-hotel` | Type of service |
| `from_location_id` | Yes | Integer | Origin location ID |
| `to_location_id` | Yes | Integer | Destination location ID |
| `pax` | Yes | 1-50 | Number of passengers |
| `date` | Optional | YYYY-MM-DD | Service date (defaults to today) |
| `locale` | Optional | `en`, `es`, `fr` | Override Accept-Language header |

#### Real Examples

**One-Way Airport to Hotel:**
```http
GET /api/v1/quote?service_type=one-way&from_location_id=1&to_location_id=2&pax=2&date=2025-12-25
Accept-Language: en-US,en;q=0.9
```

**Round Trip with Spanish Response:**
```http
GET /api/v1/quote?locale=es&service_type=round-trip&from_location_id=1&to_location_id=3&pax=4&date=2025-12-25
```

**Hotel-to-Hotel Transfer:**
```http
GET /api/v1/quote?service_type=hotel-to-hotel&from_location_id=5&to_location_id=8&pax=2
Accept-Language: fr-FR,fr;q=0.9
```

### Quote Response Structure
```json
{
  "success": true,
  "message": "Quote calculated successfully",
  "data": {
    "exchangeDollar": "1.000000",
    "exchangeMXN": "0.050251",
    "currency": "usd",
    "fromHotelId": "1",
    "toHotelId": "2",
    "fromHotel": "CANCUN INTERNATIONAL AIRPORT",
    "toHotel": "GRAND OASIS CANCUN",
    "toDestination": "cancun",
    "toDestinationId": 1,
    "fromDestination": "cancun",
    "fromDestinationId": 1,
    "serviceTypeTPV": "service_airport",
    "prices": [
      {
        "id": 1,
        "name": "Standard Sedan",
        "pic": "https://example.com/sedan.jpg",
        "type": "SED",
        "features": [...],
        "mUnits": 4,
        "mPax": 3,
        "timeFromAirport": 30,
        "costVehicleOW": "120.00",
        "totalOW": 120,
        "costVehicleRT": "200.00",
        "totalRT": 200,
        "available": 1
      }
    ]
  }
}
```

## 💰 Rates Management

### List Rates with Filters

```http
GET /api/v1/rates?{filter_parameters}&{pagination_parameters}&{sorting_parameters}
```

#### Filter Parameters

| Parameter | Optional | Values | Description |
|-----------|----------|--------|-------------|
| `service_type_id` | Yes | Integer | Filter by service type |
| `vehicle_type_id` | Yes | Integer | Filter by vehicle type |
| `from_zone_id` | Yes | Integer | Filter by origin zone |
| `to_zone_id` | Yes | Integer | Filter by destination zone |
| `from_location_id` | Yes | Integer | Filter by origin location |
| `to_location_id` | Yes | Integer | Filter by destination location |
| `available` | Yes | `true`, `false` | Filter by availability |
| `rate_type` | Yes | `zone`, `location` | Filter by pricing type |
| `valid_date` | Yes | YYYY-MM-DD | Filter by date validity |

#### Pagination Parameters

| Parameter | Optional | Default | Description |
|-----------|----------|---------|-------------|
| `page` | Yes | 1 | Page number |
| `per_page` | Yes | 15 | Items per page (max 100) |

#### Sorting Parameters

| Parameter | Optional | Default | Description |
|-----------|----------|---------|-------------|
| `sort_by` | Yes | `created_at` | `total_one_way`, `total_round_trip`, `created_at`, `updated_at` |
| `sort_order` | Yes | `desc` | `asc`, `desc` |

#### Real Examples

**All Available Zone-Based Rates:**
```http
GET /api/v1/rates?service_type_id=1&vehicle_type_id=2&available=true&rate_type=zone&sort_by=total_one_way&sort_order=asc&per_page=20&page=1
```

**Rates for Specific Zone Route:**
```http
GET /api/v1/rates?from_zone_id=1&to_zone_id=2&valid_date=2025-08-25&sort_by=total_round_trip&sort_order=desc
Accept-Language: es
```

**Location-Specific Rates:**
```http
GET /api/v1/rates?from_location_id=1&to_location_id=5&rate_type=location&available=true
```

### Route and Zone Rate Queries

**Route Rates (Location to Location):**
```http
GET /api/v1/rates/route?service_type_id=1&from_location_id=1&to_location_id=2&date=2025-09-15
```

**Zone Rates (Zone to Zone):**
```http
GET /api/v1/rates/zone?service_type_id=1&from_zone_id=1&to_zone_id=3&date=2025-10-01
Accept-Language: fr
```

### Create Rate (POST)

**Zone-Based Rate Creation:**
```http
POST /api/v1/rates
Content-Type: application/json
Accept-Language: es

{
  "service_type_id": 1,
  "vehicle_type_id": 2,
  "from_zone_id": 1,
  "to_zone_id": 2,
  "cost_vehicle_one_way": 120.00,
  "total_one_way": 150.00,
  "cost_vehicle_round_trip": 200.00,
  "total_round_trip": 250.00,
  "num_vehicles": 1,
  "available": true,
  "valid_from": "2025-08-01",
  "valid_to": "2025-12-31"
}
```

**Location-Specific Rate Creation:**
```http
POST /api/v1/rates
Content-Type: application/json

{
  "service_type_id": 1,
  "vehicle_type_id": 1,
  "from_location_id": 1,
  "to_location_id": 5,
  "cost_vehicle_one_way": 80.00,
  "total_one_way": 100.00,
  "cost_vehicle_round_trip": 140.00,
  "total_round_trip": 180.00,
  "num_vehicles": 1,
  "available": true,
  "valid_from": "2025-08-01",
  "valid_to": "2025-12-31"
}
```

### Update Rate (PUT)

```http
PUT /api/v1/rates/1
Content-Type: application/json
Accept-Language: fr

{
  "cost_vehicle_one_way": 130.00,
  "total_one_way": 160.00,
  "cost_vehicle_round_trip": 220.00,
  "total_round_trip": 280.00,
  "available": false
}
```

## 📍 Location Management

### List and Filter Locations

**All Active Locations:**
```http
GET /api/v1/locations
```

**Locations by City:**
```http
GET /api/v1/cities/1/locations
Accept-Language: es
```

**Locations by Type:**
```http
GET /api/v1/locations/type/H
Accept-Language: fr
```
- `H` = Hotels
- `A` = Airports  
- `R` = Restaurants
- `T` = Tours

### Create Location (POST)

```http
POST /api/v1/locations
Content-Type: application/json
Accept-Language: es

{
  "name": "Hotel Paraíso Maya",
  "city_id": 1,
  "type": "H",
  "active": true,
  "description": "Luxury beachfront resort in Riviera Maya",
  "latitude": 20.6296,
  "longitude": -87.0739
}
```

### Update Location (PUT)

```http
PUT /api/v1/locations/5
Content-Type: application/json
Accept-Language: fr

{
  "name": "Hôtel Paradis Maya - Updated",
  "description": "Resort de luxe en bord de mer à Riviera Maya",
  "active": false
}
```

## 🏙️ City Management

### City Operations

**List All Cities:**
```http
GET /api/v1/cities
Accept-Language: en
```

**City with Details:**
```http
GET /api/v1/cities/1/details
Accept-Language: es
```

**Create City:**
```http
POST /api/v1/cities
Content-Type: application/json
Accept-Language: es

{
  "name": "Puerto Vallarta",
  "state": "Jalisco",
  "country": "Mexico",
  "code": "PVR",
  "active": true,
  "latitude": 20.6534,
  "longitude": -105.2253
}
```

## 🗺️ Zone Management

### Zone Operations

**List All Zones:**
```http
GET /api/v1/zones
```

**Zones by City:**
```http
GET /api/v1/cities/1/zones
Accept-Language: es
```

**Create Zone with Geometry:**
```http
POST /api/v1/zones
Content-Type: application/json

{
  "name": "Hotel Zone North",
  "city_id": 1,
  "active": true,
  "description": "Northern hotel zone with luxury resorts",
  "geometry": {
    "type": "Polygon",
    "coordinates": [[[-87.07, 20.62], [-87.08, 20.62], [-87.08, 20.63], [-87.07, 20.63], [-87.07, 20.62]]]
  }
}
```

## 🚗 Vehicle Type Management

### Vehicle Type Operations

**List All Vehicle Types:**
```http
GET /api/v1/vehicle-types
Accept-Language: en
```

**Create Vehicle Type:**
```http
POST /api/v1/vehicle-types
Content-Type: application/json
Accept-Language: fr

{
  "name": "Luxury Van Premium",
  "code": "LVP",
  "max_pax": 8,
  "max_units": 12,
  "travel_time": 45,
  "image": "https://example.com/images/luxury-van.jpg",
  "video_url": "https://example.com/videos/luxury-van-tour.mp4",
  "frame": "modern",
  "active": true
}
```

## ⭐ Service Features

### Service Feature Operations

**List All Features:**
```http
GET /api/v1/service-features
Accept-Language: en
```

**Create Multilingual Service Feature:**
```http
POST /api/v1/service-features
Content-Type: application/json
Accept-Language: fr

{
  "name": {
    "en": "Premium WiFi",
    "es": "WiFi Premium",
    "fr": "WiFi Premium"
  },
  "description": {
    "en": "High-speed internet access during your trip",
    "es": "Acceso a internet de alta velocidad durante tu viaje",
    "fr": "Accès internet haute vitesse pendant votre voyage"
  },
  "icon": "wifi",
  "active": true
}
```

## 🔧 Health Check

### System Health Monitoring

```http
GET /api/v1/health
```

**Response:**
```json
{
  "success": true,
  "message": "API is healthy",
  "timestamp": "2025-08-25T10:30:00.000000Z",
  "version": "v1",
  "services": {
    "cache": "connected",
    "database": "connected"
  },
  "uptime": {
    "started_at": "2025-08-25T08:00:00.000000Z",
    "uptime_seconds": 9000
  }
}
```

## 🌐 Internationalization Testing

### Language Detection Priority

1. **Query Parameter Override:**
   ```http
   GET /api/v1/quotes?locale=fr&service_type=one-way&from_location_id=1&to_location_id=2&pax=2
   Accept-Language: es
   ```
   Result: French response (query parameter wins)

2. **Accept-Language Header:**
   ```http
   GET /api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2
   Accept-Language: es-MX,es;q=0.9,en;q=0.8
   ```
   Result: Spanish response

3. **Quality Value Priority:**
   ```http
   GET /api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2
   Accept-Language: fr;q=0.9,es;q=0.8,en;q=0.7
   ```
   Result: French response (highest quality value)

4. **Unsupported Language Fallback:**
   ```http
   GET /api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2
   Accept-Language: de-DE,de;q=0.9
   ```
   Result: English response (fallback)

## ❌ Error Handling Examples

### Validation Errors

**Missing Required Parameters:**
```http
GET /api/v1/autocomplete?q=test
Accept-Language: es

Response (422):
{
  "success": false,
  "message": "La validación ha fallado",
  "errors": {
    "lang": ["El campo lang es obligatorio"],
    "type": ["El campo type es obligatorio"],
    "input": ["El campo input es obligatorio"]
  }
}
```

**Invalid Service Type:**
```http
GET /api/v1/quote?service_type=invalid&from_location_id=1&to_location_id=2&pax=2
Accept-Language: fr

Response (422):
{
  "success": false,
  "message": "Échec de la validation",
  "errors": {
    "service_type": ["Le service type doit être un de: round-trip, one-way, hotel-to-hotel"]
  }
}
```

### Resource Not Found

```http
GET /api/v1/locations/99999
Accept-Language: es

Response (404):
{
  "success": false,
  "message": "Ubicación no encontrada",
  "timestamp": "2025-08-25T10:30:00.000000Z",
  "request_id": "req_12345"
}
```

### Rate Limit Errors

```http
GET /api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2

Response (429):
{
  "success": false,
  "message": "Too many requests",
  "timestamp": "2025-08-25T10:30:00.000000Z",
  "request_id": "req_12345"
}
```

## 📊 Testing Best Practices

### 1. Use Environment Variables
```javascript
// In Postman environment:
{
  "base_url": "http://localhost:8000",
  "test_location_from": "1",
  "test_location_to": "2",
  "test_passengers": "2"
}
```

### 2. Parameter Validation
Always validate these parameter combinations:
- Autocomplete: `lang` + `type` + `input` are required
- Quote: `service_type` + `from_location_id` + `to_location_id` + `pax` are required
- Rates: Either zone-based OR location-based pricing, not both

### 3. Date Formats
Use ISO 8601 date format: `YYYY-MM-DD`
- Valid: `2025-12-25`
- Invalid: `25/12/2025`, `Dec 25, 2025`

### 4. Coordinate Formats
Use decimal degrees format:
- Latitude: `-90` to `90`
- Longitude: `-180` to `180`
- Example: `latitude: 20.6296, longitude: -87.0739`

### 5. Language Testing
Test all three languages for every endpoint:
- English: `Accept-Language: en-US,en;q=0.9`
- Spanish: `Accept-Language: es-MX,es;q=0.9`
- French: `Accept-Language: fr-FR,fr;q=0.9`

This comprehensive guide ensures you're using the API correctly with real, working parameters for all endpoints! 🚀