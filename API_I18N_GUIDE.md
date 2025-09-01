# API Internationalization (i18n) Implementation Guide

## Overview

This API now supports internationalization (i18n) with automatic locale detection and localized responses. The implementation follows Laravel best practices and provides a smooth user experience across different languages.

## Supported Languages

- **English (en)** - Default language
- **Spanish (es)** - Full translation support
- **French (fr)** - Full translation support

## Features

### 1. Automatic Locale Detection
The API automatically detects the user's preferred language using the following priority:

1. **Query Parameter**: `?locale=es`
2. **Accept-Language Header**: Browser/client language preference
3. **Session Storage**: Previously selected language
4. **Default Fallback**: English (`en`)

### 2. Localized API Responses
All API responses now include localized messages:
- Success messages
- Error messages
- Validation errors
- Resource-specific messages

### 3. Consistent Response Format
```json
{
    "success": true,
    "message": "Éxito",
    "data": { ... },
    "timestamp": "2025-08-23T...",
    "request_id": "..."
}
```

## Usage Examples

### Setting Language via Query Parameter
```bash
GET /api/v1/quotes?locale=es&service_type=one-way&from_location_id=1&to_location_id=2&pax=2
```

### Setting Language via Header
```bash
curl -H "Accept-Language: es" \
     -X GET "http://api.example.com/api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2"
```

### Multiple Language Preferences
```bash
curl -H "Accept-Language: es-MX,es;q=0.9,en;q=0.8" \
     -X GET "http://api.example.com/api/v1/quotes"
```

## Implementation Details

### Middleware: LocaleMiddleware
- Automatically applied to all API routes
- Detects and sets the application locale
- Stores preference in session
- Validates locale against supported languages

### Language Files Structure
```
resources/lang/
├── en/
│   ├── api.php          # API-specific messages
│   └── validation.php   # Validation messages
├── es/
│   ├── api.php
│   └── validation.php
└── fr/
    ├── api.php
    └── validation.php
```

### BaseApiController Enhancements
New methods for localized responses:

```php
// Resource-specific success responses
$this->resourceResponse('quote', 'calculated', $data);

// Resource-specific error responses  
$this->resourceErrorResponse('quote', 'not_found', 404);

// Automatic message localization
$this->successResponse($data); // Uses __('api.success')
$this->errorResponse();        // Uses __('api.error')
```

### Form Validation
Custom `LocalizedRequest` class provides:
- Automatic validation error translation
- Custom attribute names
- Consistent error response format

Example usage:
```php
class QuoteRequest extends LocalizedRequest
{
    public function rules(): array
    {
        return [
            'service_type' => 'required|string|in:round-trip,one-way',
            'pax' => 'required|integer|min:1|max:50',
        ];
    }
}
```

## Message Categories

### 1. General API Messages
- `api.success`
- `api.error` 
- `api.validation_failed`
- `api.not_found`
- `api.internal_server_error`

### 2. Resource-Specific Messages
- `api.resources.quote.calculated`
- `api.resources.quote.not_found`
- `api.resources.rate.created`
- `api.resources.location.invalid_coordinates`

### 3. Business Logic Messages
- `api.business.no_available_vehicles`
- `api.business.invalid_route`
- `api.business.distance_calculation_failed`

### 4. Validation Messages
- Standard Laravel validation rules
- Custom business rule validations
- Localized attribute names

## Adding New Languages

1. Create language directory: `resources/lang/{locale}/`
2. Copy and translate `api.php` and `validation.php`
3. Add locale to `config/app.php`: `'supported_locales' => ['en', 'es', 'fr', 'new_locale']`

## Testing Localization

### Via Artisan Tinker
```php
php artisan tinker
app()->setLocale('es');
echo __('api.success'); // Output: "Éxito"
```

### Via API Testing
```bash
# Test English (default)
curl -X GET "http://api.example.com/api/v1/quotes?service_type=invalid"

# Test Spanish
curl -H "Accept-Language: es" \
     -X GET "http://api.example.com/api/v1/quotes?service_type=invalid"

# Test French  
curl -H "Accept-Language: fr" \
     -X GET "http://api.example.com/api/v1/quotes?service_type=invalid"
```

## Best Practices

1. **Always use translation keys** instead of hardcoded strings
2. **Use resource-specific methods** for consistent messaging
3. **Test all language variations** before deployment
4. **Keep translations consistent** across all languages
5. **Use proper HTTP headers** for language detection
6. **Provide fallbacks** for unsupported languages

## Performance Considerations

- Language files are cached by Laravel
- Middleware adds minimal overhead (~1-2ms)
- Session storage prevents repeated language detection
- Translation strings are loaded on-demand

## Security Notes

- Locale validation prevents injection attacks
- Only supported locales are accepted
- Session storage is properly encrypted
- No sensitive data in language files

## Future Enhancements

- Database-driven translations for dynamic content
- Right-to-left (RTL) language support
- Pluralization rules for complex languages
- Date/time localization
- Currency formatting per locale