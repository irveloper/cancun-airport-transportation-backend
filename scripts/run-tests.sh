#!/bin/bash

# Test runner script for FiveStars Backend
# This script provides convenient commands to run different types of tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Function to check if we're in the right directory
check_environment() {
    if [[ ! -f "composer.json" ]]; then
        print_error "This script must be run from the project root directory"
        exit 1
    fi

    if [[ ! -f "phpunit.xml" ]]; then
        print_error "phpunit.xml not found. Make sure you're in the Laravel project root."
        exit 1
    fi
}

# Function to setup test database
setup_test_db() {
    print_status "Setting up test database..."
    
    # Copy environment file for testing if it doesn't exist
    if [[ ! -f ".env.testing" ]]; then
        print_warning ".env.testing not found. Creating from .env.example..."
        cp .env.example .env.testing
        
        # Set testing-specific values
        sed -i.bak 's/DB_DATABASE=.*/DB_DATABASE=testing/' .env.testing
        sed -i.bak 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env.testing
        rm .env.testing.bak
    fi

    # Create testing database if using SQLite
    if ! touch database/testing.sqlite 2>/dev/null; then
        print_warning "Could not create database/testing.sqlite, it may already exist"
    fi

    print_success "Test database setup complete"
}

# Function to run migrations
run_migrations() {
    print_status "Running test migrations..."
    php artisan migrate:fresh --env=testing --force
    print_success "Migrations completed"
}

# Function to run all tests
run_all_tests() {
    print_status "Running all tests..."
    php artisan test --env=testing
}

# Function to run unit tests only
run_unit_tests() {
    print_status "Running unit tests..."
    php artisan test --testsuite=Unit --env=testing
}

# Function to run feature tests only
run_feature_tests() {
    print_status "Running feature tests..."
    php artisan test --testsuite=Feature --env=testing
}

# Function to run tests with coverage
run_tests_with_coverage() {
    print_status "Running tests with coverage..."
    php artisan test --env=testing --coverage-text
}

# Function to run specific test file
run_specific_test() {
    if [[ -z "$1" ]]; then
        print_error "Please provide a test file path"
        print_status "Usage: $0 specific tests/Unit/ExampleTest.php"
        exit 1
    fi
    
    print_status "Running specific test: $1"
    php artisan test --env=testing "$1"
}

# Function to run tests for autocomplete functionality
run_autocomplete_tests() {
    print_status "Running autocomplete tests..."
    php artisan test --env=testing tests/Unit/AutocompleteControllerTest.php
}

# Function to run tests for rates functionality
run_rate_tests() {
    print_status "Running rate tests..."
    php artisan test --env=testing tests/Unit/RateModelTest.php tests/Unit/RateControllerTest.php
}

# Function to run performance tests
run_performance_tests() {
    print_status "Running performance tests..."
    php artisan test --env=testing tests/Feature/ApiPerformanceTest.php
}

# Function to run integration tests
run_integration_tests() {
    print_status "Running integration tests..."
    php artisan test --env=testing tests/Feature/ApiIntegrationTest.php
}

# Function to watch tests (requires entr or similar)
watch_tests() {
    print_status "Watching for file changes and running tests..."
    if command -v entr &> /dev/null; then
        find . -name "*.php" | entr -c php artisan test --env=testing
    elif command -v fswatch &> /dev/null; then
        fswatch -o . | xargs -n1 -I{} php artisan test --env=testing
    else
        print_error "File watching requires 'entr' or 'fswatch' to be installed"
        print_status "Install with: brew install entr (macOS) or apt-get install entr (Linux)"
        exit 1
    fi
}

# Function to clean up test artifacts
cleanup_tests() {
    print_status "Cleaning up test artifacts..."
    
    # Remove test database
    if [[ -f "database/testing.sqlite" ]]; then
        rm database/testing.sqlite
        print_status "Removed test database"
    fi
    
    # Clear test cache
    php artisan config:clear --env=testing
    php artisan cache:clear --env=testing
    
    print_success "Cleanup completed"
}

# Function to run quick smoke tests
run_smoke_tests() {
    print_status "Running smoke tests (quick validation)..."
    php artisan test --env=testing --stop-on-failure --group=smoke
}

# Function to validate test setup
validate_setup() {
    print_status "Validating test setup..."
    
    # Check PHP version
    php_version=$(php -r "echo PHP_VERSION;")
    print_status "PHP Version: $php_version"
    
    # Check if required extensions are loaded
    extensions=("pdo" "sqlite3" "mbstring" "tokenizer")
    for ext in "${extensions[@]}"; do
        if php -m | grep -q "^$ext\$"; then
            print_success "Extension $ext is loaded"
        else
            print_error "Extension $ext is not loaded"
        fi
    done
    
    # Check if test files exist
    test_files=(
        "tests/Unit/AutocompleteControllerTest.php"
        "tests/Unit/RateModelTest.php"
        "tests/Unit/RateControllerTest.php"
        "tests/Feature/ApiIntegrationTest.php"
        "tests/Feature/ApiPerformanceTest.php"
    )
    
    for file in "${test_files[@]}"; do
        if [[ -f "$file" ]]; then
            print_success "Test file exists: $file"
        else
            print_error "Test file missing: $file"
        fi
    done
}

# Function to show help
show_help() {
    echo "FiveStars Backend Test Runner"
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  setup          - Setup test environment and database"
    echo "  all            - Run all tests"
    echo "  unit           - Run only unit tests"
    echo "  feature        - Run only feature tests"
    echo "  coverage       - Run tests with coverage report"
    echo "  autocomplete   - Run autocomplete functionality tests"
    echo "  rates          - Run rates functionality tests"
    echo "  performance    - Run performance tests"
    echo "  integration    - Run integration tests"
    echo "  smoke          - Run quick smoke tests"
    echo "  specific FILE  - Run specific test file"
    echo "  watch          - Watch files and run tests on change"
    echo "  cleanup        - Clean up test artifacts"
    echo "  validate       - Validate test setup"
    echo "  help           - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 setup"
    echo "  $0 all"
    echo "  $0 specific tests/Unit/RateModelTest.php"
    echo "  $0 autocomplete"
}

# Main script logic
main() {
    check_environment
    
    case "${1:-help}" in
        "setup")
            setup_test_db
            run_migrations
            validate_setup
            ;;
        "all")
            setup_test_db
            run_all_tests
            ;;
        "unit")
            setup_test_db
            run_unit_tests
            ;;
        "feature")
            setup_test_db
            run_feature_tests
            ;;
        "coverage")
            setup_test_db
            run_tests_with_coverage
            ;;
        "autocomplete")
            setup_test_db
            run_autocomplete_tests
            ;;
        "rates")
            setup_test_db
            run_rate_tests
            ;;
        "performance")
            setup_test_db
            run_performance_tests
            ;;
        "integration")
            setup_test_db
            run_integration_tests
            ;;
        "smoke")
            setup_test_db
            run_smoke_tests
            ;;
        "specific")
            setup_test_db
            run_specific_test "$2"
            ;;
        "watch")
            setup_test_db
            watch_tests
            ;;
        "cleanup")
            cleanup_tests
            ;;
        "validate")
            validate_setup
            ;;
        "help"|*)
            show_help
            ;;
    esac
}

# Run main function with all arguments
main "$@"