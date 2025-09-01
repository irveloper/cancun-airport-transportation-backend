# Postman Collection Setup Guide for FiveStars API i18n Testing

## 📦 Collection Overview

The **FiveStars API - Internationalization (i18n)** collection provides comprehensive testing for all internationalization features implemented in the API. It includes:

- ✅ **87 test requests** covering all endpoints
- 🌐 **3 languages**: English, Spanish, French  
- 📝 **Automatic validation** of localized responses
- 🔄 **Dynamic language switching** capabilities
- 📊 **Environment-specific configurations**

## 🚀 Quick Setup

### 1. Import Collection and Environments

```bash
# In Postman, import these files:
- postman/FiveStars-API-i18n.postman_collection.json
- postman/FiveStars-Development-EN.postman_environment.json
- postman/FiveStars-Development-ES.postman_environment.json  
- postman/FiveStars-Development-FR.postman_environment.json
```

### 2. Configure Base URL

Update the `base_url` variable in each environment:

```json
{
  "key": "base_url",
  "value": "http://localhost:8000"  // Change this to your API URL
}
```

### 3. Test Data Setup

Ensure your database has test data:

```bash
# Run seeders to populate test data
php artisan db:seed --class=LocationSeeder
php artisan db:seed --class=VehicleTypeSeeder  
php artisan db:seed --class=RateSeeder
```

## 📁 Collection Structure

### 🌐 Locale Detection Examples
Tests various methods of language detection:
- Query parameters (`?locale=es`)
- Accept-Language headers
- Priority handling with quality values
- Fallback to English for unsupported languages

### ❌ Validation Error Examples  
Comprehensive validation testing:
- Missing required fields
- Invalid service types
- Invalid passenger counts
- Non-existent location IDs
- Same pickup/dropoff locations

### 📍 Resource Endpoints
Tests for all API endpoints:
- **Locations**: CRUD operations with localized responses
- **Vehicle Types**: Listing and details
- **Rates**: Management with zone-based pricing
- **Cities**: Geographic data operations
- **Autocomplete**: Search functionality

### 🧪 Dynamic Language Switching
Advanced testing with pre-request scripts that:
- Randomly switch between languages
- Test locale persistence
- Validate response consistency

## 🔧 Environment Variables

### Common Variables (All Environments)
```json
{
  "base_url": "http://localhost:8000",
  "api_version": "v1",
  "content_type": "application/json",
  "test_location_from": "1",
  "test_location_to": "2", 
  "test_passengers": "2",
  "test_service_type": "one-way"
}
```

### Language-Specific Variables

#### English Environment
```json
{
  "locale": "en",
  "accept_language": "en-US,en;q=0.9",
  "expected_success_message": "calculated successfully",
  "expected_validation_message": "Validation failed",
  "expected_not_found_message": "not found"
}
```

#### Spanish Environment  
```json
{
  "locale": "es",
  "accept_language": "es-MX,es;q=0.9,en;q=0.8",
  "expected_success_message": "exitosamente",
  "expected_validation_message": "validación ha fallado", 
  "expected_not_found_message": "no encontrada"
}
```

#### French Environment
```json
{
  "locale": "fr", 
  "accept_language": "fr-FR,fr;q=0.9,en;q=0.8",
  "expected_success_message": "succès",
  "expected_validation_message": "validation",
  "expected_not_found_message": "non trouvé"
}
```

## 🧪 Testing Scenarios

### 1. Basic Locale Detection Tests

**Test Query Parameter Priority:**
```http
GET /api/v1/quotes?locale=fr&service_type=one-way&from_location_id=1&to_location_id=2&pax=2
Accept-Language: es
```
Expected: French response (query param overrides header)

**Test Accept-Language Header:**
```http  
GET /api/v1/quotes?service_type=one-way&from_location_id=1&to_location_id=2&pax=2
Accept-Language: es-MX,es;q=0.9,en;q=0.8
```
Expected: Spanish response

### 2. Validation Error Tests

**Missing Required Fields:**
```http
GET /api/v1/quotes
Accept-Language: es
```
Expected Result:
```json
{
  "success": false,
  "message": "La validación ha fallado",
  "errors": {
    "service_type": ["El campo service type es obligatorio"],
    "from_location_id": ["El campo pickup location es obligatorio"]
  }
}
```

**Invalid Service Type:**
```http
GET /api/v1/quotes?service_type=invalid&from_location_id=1&to_location_id=2&pax=2
Accept-Language: fr
```
Expected: French validation error message

### 3. Resource-Specific Tests

**Location Not Found:**
```http
GET /api/v1/locations/99999
Accept-Language: es
```
Expected Result:
```json
{
  "success": false,
  "message": "Ubicación no encontrada"
}
```

### 4. Dynamic Language Switching

The collection includes a special test that uses pre-request scripts to:
1. Randomly select a language (en/es/fr)
2. Set appropriate headers
3. Validate the response matches the selected language

## 📊 Test Execution

### Run Individual Tests
1. Select desired environment (English/Spanish/French)
2. Click on any request in the collection
3. Click "Send" 
4. Review the test results in the "Test Results" tab

### Run Collection Tests
1. Right-click on collection name
2. Select "Run collection"
3. Choose environment
4. Configure iterations and delay if needed
5. Click "Run FiveStars API - i18n"

### Automated Test Validation

Each request includes automatic tests that verify:

```javascript
// Common assertions for all requests
pm.test("Status code is correct", function () {
    pm.response.to.have.status(200); // or expected status
});

pm.test("Response has correct structure", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData).to.have.property('message'); 
    pm.expect(jsonData).to.have.property('timestamp');
    pm.expect(jsonData).to.have.property('request_id');
});

pm.test("Response is in correct language", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include(pm.environment.get('expected_success_message'));
});

pm.test("Timestamp is valid ISO format", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.timestamp).to.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
});
```

## 🔍 Debugging Tips

### Check Environment Variables
```javascript
// In Pre-request Script or Tests, log current variables:
console.log('Current locale:', pm.environment.get('locale'));
console.log('Accept-Language:', pm.environment.get('accept_language'));
console.log('Base URL:', pm.environment.get('base_url'));
```

### Validate Request Headers
```javascript  
// Check if headers are set correctly:
pm.test("Accept-Language header is set", function () {
    pm.expect(pm.request.headers.get('Accept-Language')).to.exist;
});
```

### Custom Response Logging
```javascript
// Log response for debugging:
const jsonData = pm.response.json();
console.log('Response message:', jsonData.message);
console.log('Response locale detected:', jsonData.locale);
```

## 🌟 Advanced Usage

### Custom Test Scripts

Add these to individual requests for enhanced testing:

```javascript
// Validate specific error codes per language
pm.test("Error message contains expected text", function () {
    const jsonData = pm.response.json();
    const locale = pm.environment.get('locale');
    
    if (locale === 'es') {
        pm.expect(jsonData.message).to.include('error');
    } else if (locale === 'fr') {
        pm.expect(jsonData.message).to.include('erreur');
    } else {
        pm.expect(jsonData.message).to.include('error');
    }
});

// Performance testing
pm.test("Response time is acceptable", function () {
    pm.expect(pm.response.responseTime).to.be.below(1000);
});

// Data integrity checks
pm.test("Quote response has required fields", function () {
    if (pm.response.code === 200) {
        const jsonData = pm.response.json();
        pm.expect(jsonData.data).to.have.property('currency');
        pm.expect(jsonData.data).to.have.property('prices');
        pm.expect(jsonData.data.prices).to.be.an('array');
    }
});
```

### Environment Switching Scripts

```javascript
// Pre-request script to cycle through environments
const environments = ['English', 'Español', 'Français'];
const currentEnv = pm.environment.name;
const currentIndex = environments.indexOf(currentEnv);
const nextIndex = (currentIndex + 1) % environments.length;

// Set variable for next test
pm.globals.set('next_environment', environments[nextIndex]);
```

## 📈 Monitoring & Reports

### Newman CLI Testing

Run the collection from command line:

```bash
# Install Newman
npm install -g newman

# Run with English environment  
newman run postman/FiveStars-API-i18n.postman_collection.json \
       -e postman/FiveStars-Development-EN.postman_environment.json \
       --reporters cli,html \
       --reporter-html-export results-en.html

# Run with Spanish environment
newman run postman/FiveStars-API-i18n.postman_collection.json \
       -e postman/FiveStars-Development-ES.postman_environment.json \
       --reporters cli,html \  
       --reporter-html-export results-es.html

# Run with French environment
newman run postman/FiveStars-API-i18n.postman_collection.json \
       -e postman/FiveStars-Development-FR.postman_environment.json \
       --reporters cli,html \
       --reporter-html-export results-fr.html
```

### CI/CD Integration

Add to your pipeline:

```yaml
# .github/workflows/api-tests.yml
name: API i18n Tests

on: [push, pull_request]

jobs:
  api-tests:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        locale: [EN, ES, FR]
    
    steps:
    - uses: actions/checkout@v2
    - name: Setup Node.js
      uses: actions/setup-node@v2
      with:
        node-version: '16'
    
    - name: Install Newman
      run: npm install -g newman
      
    - name: Run API Tests - ${{ matrix.locale }}
      run: |
        newman run postman/FiveStars-API-i18n.postman_collection.json \
               -e postman/FiveStars-Development-${{ matrix.locale }}.postman_environment.json \
               --reporters cli,junit \
               --reporter-junit-export results-${{ matrix.locale }}.xml
    
    - name: Upload Test Results  
      uses: actions/upload-artifact@v2
      with:
        name: test-results-${{ matrix.locale }}
        path: results-${{ matrix.locale }}.xml
```

## 🚨 Troubleshooting

### Common Issues

**1. "Connection refused" errors:**
- Verify Laravel server is running: `php artisan serve`
- Check `base_url` in environment matches server address
- Ensure no firewall blocking the port

**2. "404 Not Found" errors:**
- Verify routes are registered: `php artisan route:list | grep api/v1`
- Check API URL structure matches collection requests
- Ensure middleware is not blocking requests

**3. "Validation failed" in unexpected language:**
- Check Accept-Language header syntax
- Verify locale files exist for the requested language
- Check Laravel locale configuration in `config/app.php`

**4. Tests failing with "Expected message not found":**
- Update `expected_*_message` variables in environments  
- Check actual API responses to verify translation keys
- Ensure translation files are properly loaded

### Debug Mode

Enable detailed logging by adding to Pre-request Scripts:

```javascript
// Enable console logging
pm.globals.set('debug_mode', 'true');

// Log all request details
console.log('=== REQUEST DEBUG ===');
console.log('URL:', pm.request.url.toString());
console.log('Method:', pm.request.method);
console.log('Headers:', pm.request.headers);
console.log('Body:', pm.request.body);
console.log('Environment:', pm.environment.name);
```

And in Test Scripts:

```javascript
// Log response details if debug mode is on
if (pm.globals.get('debug_mode') === 'true') {
    console.log('=== RESPONSE DEBUG ===');  
    console.log('Status:', pm.response.code);
    console.log('Headers:', pm.response.headers);
    console.log('Body:', pm.response.text());
    console.log('Response Time:', pm.response.responseTime + 'ms');
}
```

## 🎯 Best Practices

1. **Always use environment variables** - Don't hardcode URLs or test data
2. **Test all three languages** - Switch environments regularly  
3. **Validate response structure** - Don't just check status codes
4. **Use meaningful test names** - Make failures easy to identify
5. **Monitor response times** - Set appropriate thresholds
6. **Keep environments in sync** - Update all when making changes
7. **Document custom tests** - Add comments to complex assertions

This comprehensive testing setup ensures your i18n implementation works correctly across all supported languages and use cases! 🚀