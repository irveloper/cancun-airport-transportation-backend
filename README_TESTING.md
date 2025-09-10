# Testing Guide for FiveStars Backend

This guide covers the comprehensive testing setup created for the FiveStars Backend, focusing on autocomplete and rates functionality with overall best practices.

## Overview

The testing suite includes:

- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test API endpoints and integration scenarios  
- **Performance Tests**: Validate response times and resource usage
- **Integration Tests**: Test end-to-end workflows and API consistency

## Test Structure

```
tests/
├── bootstrap.php                     # Test bootstrap configuration
├── Helpers/
│   └── TestHelper.php               # Reusable test helper functions
├── Unit/
│   ├── AutocompleteControllerTest.php # Unit tests for autocomplete functionality
│   ├── RateModelTest.php            # Unit tests for Rate model
│   └── RateControllerTest.php       # Unit tests for Rate controller
└── Feature/
    ├── ApiIntegrationTest.php       # End-to-end API integration tests
    └── ApiPerformanceTest.php       # Performance and load testing
```

## Quick Start

### Using the Test Runner Script

The project includes a convenient test runner script:

```bash
# Make script executable (first time only)
chmod +x scripts/run-tests.sh

# Setup test environment
./scripts/run-tests.sh setup

# Run all tests
./scripts/run-tests.sh all

# Run specific test suites
./scripts/run-tests.sh unit
./scripts/run-tests.sh feature
./scripts/run-tests.sh autocomplete
./scripts/run-tests.sh rates
./scripts/run-tests.sh performance

# Run with coverage
./scripts/run-tests.sh coverage
```

### Manual Commands

```bash
# Run all tests
php artisan test --env=testing

# Run specific test suites
php artisan test --testsuite=Unit --env=testing
php artisan test --testsuite=Feature --env=testing

# Run specific test files
php artisan test tests/Unit/AutocompleteControllerTest.php --env=testing
php artisan test tests/Unit/RateModelTest.php --env=testing

# Run with coverage
php artisan test --coverage-text --env=testing
```

## Test Categories

### 1. Autocomplete Tests (`AutocompleteControllerTest.php`)

Tests the autocomplete search functionality:

- ✅ Empty query handling
- ✅ Search parameter validation  
- ✅ Airport vs destination context switching
- ✅ Location, zone, and airport search
- ✅ Case-insensitive search
- ✅ Result limiting and pagination
- ✅ Grouped location structure
- ✅ Search priority and ranking
- ✅ Inactive location exclusion

**Key Test Cases:**
```php
test_search_with_empty_query_returns_empty_results()
test_search_shows_airports_for_departure_from() 
test_search_location_by_name()
test_search_excludes_inactive_locations()
test_validation_errors()
```

### 2. Rate Tests (`RateModelTest.php`, `RateControllerTest.php`)

#### Rate Model Tests
- ✅ Zone-based vs location-specific rates
- ✅ Date validity and availability filtering
- ✅ Rate relationships and scopes
- ✅ Cache behavior and performance
- ✅ Price formatting and calculations

#### Rate Controller Tests  
- ✅ CRUD operations (Create, Read, Update, Delete)
- ✅ Pagination and filtering
- ✅ Route rate calculations
- ✅ Zone rate retrieval
- ✅ Validation and error handling
- ✅ Response structure consistency

**Key Test Cases:**
```php
test_can_create_zone_based_rate()
test_find_for_route_prioritizes_location_specific_rates()
test_store_creates_new_zone_based_rate()
test_get_route_rates()
test_validation_errors()
```

### 3. Integration Tests (`ApiIntegrationTest.php`)

End-to-end testing of API workflows:

- ✅ Autocomplete → Rate lookup workflow
- ✅ API response consistency across endpoints
- ✅ Error handling uniformity
- ✅ Security header validation
- ✅ Data sanitization and XSS prevention
- ✅ Pagination consistency
- ✅ API versioning structure

**Key Test Cases:**
```php
test_autocomplete_to_rates_workflow()
test_api_response_consistency()
test_error_handling_consistency()
test_data_validation_and_sanitization()
```

### 4. Performance Tests (`ApiPerformanceTest.php`)

Load and performance validation:

- ✅ Large dataset handling
- ✅ Database query efficiency (N+1 prevention)
- ✅ Memory usage monitoring
- ✅ Response time validation
- ✅ Concurrent request simulation
- ✅ Cache effectiveness testing
- ✅ Complex filtering performance

**Performance Benchmarks:**
- Autocomplete: < 2000ms with large datasets
- Rate queries: < 1500ms with pagination
- Database queries: < 15 queries per request
- Memory usage: < 50MB per request

## Test Helpers

The `TestHelper.php` provides convenient functions:

```php
// Create test data
create_test_city(['name' => 'Miami'])
create_test_zone(['name' => 'South Beach']) 
create_test_rate(['cost_vehicle_one_way' => 50.00])

// API assertions
assert_api_success($response, 200)
assert_api_error($response, 422)

// Performance measurement
measure_execution_time(function() { /* test code */ })

// Database utilities
clean_test_database()
seed_test_data()
```

## Configuration

### Test Environment (`.env.testing`)

Key testing configurations:
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
BCRYPT_ROUNDS=4
```

### PHPUnit Configuration

Tests use SQLite in-memory database for speed and isolation. Each test gets a fresh database state.

## Best Practices Covered

### 1. Test Isolation
- Each test runs with fresh database state
- Cache is cleared between tests
- No shared state between tests

### 2. Performance Testing
- Response time validation
- Memory usage monitoring  
- Database query counting
- Large dataset simulation

### 3. API Consistency
- Standardized response formats
- Consistent error handling
- Uniform timestamp formatting
- Request ID tracking

### 4. Security Testing
- Input sanitization validation
- XSS prevention checks
- SQL injection prevention
- Rate limiting simulation

### 5. Data Integrity
- Foreign key constraint validation
- Data relationship testing
- Cache invalidation testing
- Transaction rollback testing

## Running Specific Test Groups

```bash
# Autocomplete functionality tests
./scripts/run-tests.sh autocomplete

# Rate functionality tests  
./scripts/run-tests.sh rates

# Performance tests only
./scripts/run-tests.sh performance

# Integration tests only
./scripts/run-tests.sh integration

# Quick smoke tests
./scripts/run-tests.sh smoke
```

## Test Data Management

### Factory Pattern Usage
Tests use Laravel factories and helper functions to create consistent test data:

```php
// In tests
$testData = $this->createTestData();
$rate = create_test_rate(['total_one_way' => 75.00]);
```

### Database Seeding
The `seed_test_data()` helper creates a complete set of related test objects:

```php
$data = seed_test_data();
// Returns: city, service_type, vehicle_type, zones, locations, airport, rate
```

## Debugging Tests

### Enable Query Logging
```php
DB::enableQueryLog();
// ... test code ...
$queries = DB::getQueryLog();
dd($queries);
```

### Performance Profiling
```php
$metrics = $this->measureExecutionTime(function() {
    return $this->get('/api/v1/autocomplete/search?q=Miami');
});

echo "Execution time: {$metrics['execution_time_ms']}ms\n";
echo "Memory used: {$metrics['memory_used_bytes']} bytes\n";
```

### Test Output Verbosity
```bash
# Detailed output
php artisan test --verbose

# Stop on first failure
php artisan test --stop-on-failure

# Filter specific tests
php artisan test --filter=autocomplete
```

## Continuous Integration

The test suite is designed to run in CI environments:

```bash
# CI-friendly commands
./scripts/run-tests.sh setup && ./scripts/run-tests.sh all
```

Expected CI execution time: 2-5 minutes depending on system resources.

## Coverage Goals

- **Unit Tests**: 90%+ coverage of business logic
- **Integration Tests**: All API endpoints tested
- **Performance Tests**: All critical paths validated
- **Error Scenarios**: All error conditions covered

## Maintenance

### Adding New Tests

1. Use existing patterns from current tests
2. Follow the naming convention: `test_description_of_what_is_being_tested()`
3. Use helper functions for common operations
4. Include both positive and negative test cases
5. Add performance assertions for new endpoints

### Updating Test Data

When adding new models or relationships:

1. Update `TestHelper.php` with new factory functions
2. Update `seed_test_data()` if needed
3. Add cleanup logic to `clean_test_database()` if required

This comprehensive testing setup ensures the FiveStars Backend API is robust, performant, and maintainable.