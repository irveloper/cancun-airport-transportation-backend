<?php

/*
|--------------------------------------------------------------------------
| Test Bootstrap File
|--------------------------------------------------------------------------
|
| This file is executed before any test is run. It's a good place to
| set up global test configurations, register test helpers, or perform
| any other setup that should be done before tests run.
|
*/

// Set memory limit for tests
ini_set('memory_limit', '256M');

// Set time limit for tests
set_time_limit(300); // 5 minutes

// Ensure we're in testing environment
if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

// Register additional test helpers or utilities here if needed
if (file_exists(__DIR__ . '/Helpers/TestHelper.php')) {
    require_once __DIR__ . '/Helpers/TestHelper.php';
}

// Set default timezone for consistent test results
date_default_timezone_set('UTC');

// Disable output buffering for better error reporting in tests
if (ob_get_level()) {
    ob_end_clean();
}

// Enable strict error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure SQLite is available for testing
if (! extension_loaded('sqlite3') && ! extension_loaded('pdo_sqlite')) {
    echo "SQLite extension is required for testing. Please install php-sqlite3.\n";
    exit(1);
}