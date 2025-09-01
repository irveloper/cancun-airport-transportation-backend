# API Internationalization Examples

## Quick Start

### Setting Language via Query Parameter
```bash
# English (default)
curl "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2"

# Spanish
curl "http://api.example.com/api/v1/quotes?locale=es&service_type=one-way&from_location_id=1&to_location_id=2&pax=2"

# French
curl "http://api.example.com/api/v1/quotes?locale=fr&service_type=one-way&from_location_id=1&to_location_id=2&pax=2"
```

### Setting Language via Accept-Language Header
```bash
# Spanish
curl -H "Accept-Language: es" \
     "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2"

# French
curl -H "Accept-Language: fr" \
     "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2"

# Priority: Spanish preferred, English fallback
curl -H "Accept-Language: es-MX,es;q=0.9,en;q=0.8" \
     "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2"
```

## Response Examples

### Successful Quote Response

#### English (default)
```json
{
    "success": true,
    "message": "Quote calculated successfully",
    "data": {
        "currency": "usd",
        "fromHotel": "AIRPORT TERMINAL 1",
        "toHotel": "HOTEL EXAMPLE",
        "prices": [...]
    },
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12345"
}
```

#### Spanish
```json
{
    "success": true,
    "message": "Cotización calculada exitosamente",
    "data": {
        "currency": "usd",
        "fromHotel": "TERMINAL AEROPUERTO 1",
        "toHotel": "HOTEL EJEMPLO",
        "prices": [...]
    },
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12345"
}
```

#### French
```json
{
    "success": true,
    "message": "Devis calculé avec succès",
    "data": {
        "currency": "usd",
        "fromHotel": "TERMINAL AÉROPORT 1",
        "toHotel": "HÔTEL EXEMPLE",
        "prices": [...]
    },
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12345"
}
```

### Error Response Examples

#### Validation Error - English
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "service_type": ["The service type must be one of: round-trip, one-way, hotel-to-hotel"],
        "pax": ["At least 1 passenger is required"]
    },
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12346"
}
```

#### Validation Error - Spanish
```json
{
    "success": false,
    "message": "La validación ha fallado",
    "errors": {
        "service_type": ["El tipo de servicio debe ser uno de: round-trip, one-way, hotel-to-hotel"],
        "pax": ["Se requiere al menos 1 pasajero"]
    },
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12346"
}
```

#### Resource Not Found - English
```json
{
    "success": false,
    "message": "Quote not found",
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12347"
}
```

#### Resource Not Found - Spanish
```json
{
    "success": false,
    "message": "Cotización no encontrada",
    "timestamp": "2025-08-23T10:30:00.000000Z",
    "request_id": "req_12347"
}
```

## JavaScript/Frontend Integration

### Using Fetch API
```javascript
// Detect browser language
const userLanguage = navigator.language.split('-')[0]; // 'en', 'es', 'fr'

// Make API call with language preference
fetch('/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2', {
    headers: {
        'Accept-Language': userLanguage,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Localized message:', data.message);
    if (!data.success) {
        // Handle localized error messages
        console.error('Error:', data.message);
        if (data.errors) {
            // Display localized validation errors
            Object.entries(data.errors).forEach(([field, messages]) => {
                console.error(`${field}:`, messages.join(', '));
            });
        }
    }
});
```

### Using Axios
```javascript
// Set default language for all requests
axios.defaults.headers.common['Accept-Language'] = 'es';

// Or per request
axios.get('/api/v1/quotes', {
    params: {
        service_type: 'one-way',
        from_location_id: 1,
        to_location_id: 2,
        pax: 2,
        locale: 'es' // Override header
    },
    headers: {
        'Accept-Language': 'fr' // This will be overridden by locale param
    }
})
.then(response => {
    console.log('Response:', response.data.message); // French message
})
.catch(error => {
    if (error.response?.data) {
        console.error('API Error:', error.response.data.message);
    }
});
```

## Mobile App Integration

### iOS (Swift)
```swift
// Detect device language
let preferredLanguage = Locale.preferredLanguages.first?.components(separatedBy: "-").first ?? "en"

// Configure URLRequest
var request = URLRequest(url: url)
request.setValue(preferredLanguage, forHTTPHeaderField: "Accept-Language")

// Make API call
URLSession.shared.dataTask(with: request) { data, response, error in
    // Handle localized response
}.resume()
```

### Android (Kotlin)
```kotlin
// Detect device language
val deviceLanguage = Locale.getDefault().language

// Configure OkHttp request
val request = Request.Builder()
    .url("https://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2")
    .addHeader("Accept-Language", deviceLanguage)
    .build()

// Make API call with OkHttp
client.newCall(request).enqueue(object : Callback {
    override fun onResponse(call: Call, response: Response) {
        // Handle localized response
        val jsonResponse = response.body?.string()
        // Parse JSON and display localized messages
    }
})
```

## Testing Commands

### Test Locale Detection
```bash
# Test query parameter priority
curl "http://api.example.com/api/v1/quotes?locale=fr&service_type=invalid" \
     -H "Accept-Language: es"
# Should return French error message

# Test header detection
curl "http://api.example.com/api/v1/quotes?service_type=invalid" \
     -H "Accept-Language: es"
# Should return Spanish error message

# Test fallback to English
curl "http://api.example.com/api/v1/quotes?service_type=invalid" \
     -H "Accept-Language: de" # Unsupported language
# Should return English error message (fallback)
```

### Test Different Validation Scenarios
```bash
# Missing required fields - Spanish
curl -X GET "http://api.example.com/api/v1/quotes" \
     -H "Accept-Language: es"

# Invalid service type - French  
curl -X GET "http://api.example.com/api/v1/quotes?service_type=invalid&from_location_id=1&to_location_id=2&pax=1" \
     -H "Accept-Language: fr"

# Invalid passenger count - Spanish
curl -X GET "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=0" \
     -H "Accept-Language: es"
```

## Best Practices for Frontend Integration

1. **Always handle localized error messages** - Don't assume English
2. **Store user language preference** - Remember their choice
3. **Provide language selector** - Let users override detection
4. **Test with different locales** - Ensure UI accommodates text length differences
5. **Handle fallback gracefully** - Default to English if translation missing
6. **Use semantic HTTP status codes** - Don't rely only on success/error flags

## Common Pitfalls to Avoid

1. **Don't hardcode language in client** - Use detection
2. **Don't ignore Accept-Language header** - Respect browser preferences  
3. **Don't assume message lengths** - Spanish/French text can be longer
4. **Don't cache responses by language** - Unless you account for locale in cache key
5. **Don't forget to test edge cases** - Unsupported languages, malformed headers