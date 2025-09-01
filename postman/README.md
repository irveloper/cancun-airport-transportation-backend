# FiveStars API Postman Collection

## 📁 Files Overview

This directory contains comprehensive Postman testing resources for the FiveStars API internationalization features:

### Collection
- **`FiveStars-API-i18n.postman_collection.json`** - Main collection with 80+ test requests

### Environment Files
- **`FiveStars-Development-EN.postman_environment.json`** - English development environment
- **`FiveStars-Development-ES.postman_environment.json`** - Spanish development environment  
- **`FiveStars-Development-FR.postman_environment.json`** - French development environment
- **`FiveStars-Production.postman_environment.json`** - Production multi-language environment

### Documentation
- **`POSTMAN_I18N_SETUP_GUIDE.md`** - Comprehensive setup and usage guide

## 🚀 Quick Start

1. **Import in Postman:**
   ```
   File → Import → Select all JSON files from this directory
   ```

2. **Select Environment:**
   - Choose the appropriate environment from the dropdown
   - Update `base_url` if needed

3. **Run Tests:**
   - Individual: Click any request → Send
   - Collection: Right-click collection → Run collection

## 🌐 Language Testing

### Switch Languages
Change the environment to test different languages:
- **English**: Use "FiveStars Development - English" environment
- **Spanish**: Use "FiveStars Development - Español" environment  
- **French**: Use "FiveStars Development - Français" environment

### Test Scenarios Included
- ✅ Locale detection (query params, headers, fallbacks)
- ✅ Validation errors in all languages
- ✅ Success responses with localized messages
- ✅ Resource-specific error messages
- ✅ Dynamic language switching
- ✅ Priority handling for multiple language preferences

## 📊 What Gets Tested

### Core API Endpoints
- `/api/v1/quotes` - Quote calculation with i18n
- `/api/v1/locations` - Location management
- `/api/v1/vehicle-types` - Vehicle type listing
- `/api/v1/rates` - Rate management
- `/api/v1/cities` - City operations
- `/api/v1/autocomplete/*` - Search functionality

### Validation Scenarios
- Missing required fields
- Invalid service types
- Invalid passenger counts
- Non-existent resources
- Business logic validations

### Language Features
- Automatic locale detection
- Header-based language selection
- Query parameter override
- Fallback to English for unsupported languages
- Proper error message translation

## 📈 Automation Ready

Run via Newman CLI:
```bash
# Install Newman
npm install -g newman

# Test all languages
newman run FiveStars-API-i18n.postman_collection.json -e FiveStars-Development-EN.postman_environment.json
newman run FiveStars-API-i18n.postman_collection.json -e FiveStars-Development-ES.postman_environment.json  
newman run FiveStars-API-i18n.postman_collection.json -e FiveStars-Development-FR.postman_environment.json
```

## 🔧 Environment Configuration

### Required Variables
All environments include these essential variables:
- `base_url` - Your API endpoint
- `locale` - Target language (en/es/fr)
- `accept_language` - HTTP header value
- `test_location_*` - Test data IDs
- `expected_*_message` - Language-specific validation strings

### Production Environment
The production environment includes additional security features:
- `api_key` - API authentication key
- `auth_token` - Bearer token for authenticated requests  
- `request_timeout` - Timeout configuration
- `rate_limit_delay` - Delay between requests

## ⚡ Performance Testing

Each test includes performance assertions:
- Response time < 2000ms
- Proper response structure validation
- Language-specific message validation
- ISO timestamp format checks

## 🛠️ Customization

### Add New Language
1. Create new environment file based on existing ones
2. Update locale variables and expected messages
3. Add to your testing rotation

### Add New Endpoints
1. Add requests to appropriate folder in collection
2. Include language-specific test assertions
3. Update environment variables as needed

### Modify Test Data
Update these variables in your environment:
- `test_location_from` / `test_location_to`
- `test_passengers`
- `test_service_type`

## 📚 Additional Resources

- **`../API_I18N_GUIDE.md`** - Complete implementation documentation
- **`../API_I18N_EXAMPLES.md`** - Usage examples and patterns
- **`POSTMAN_I18N_SETUP_GUIDE.md`** - Detailed setup instructions

## 🆘 Support

For issues or questions about the Postman collection:
1. Check the setup guide for troubleshooting tips
2. Verify environment variables are correctly configured
3. Ensure your API server is running and accessible
4. Review Laravel logs for backend errors

Happy Testing! 🎉