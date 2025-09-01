<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Set up test-specific configurations
        $this->setUpTestEnvironment();
        
        // Load test helpers
        if (file_exists(__DIR__ . '/Helpers/TestHelper.php')) {
            require_once __DIR__ . '/Helpers/TestHelper.php';
        }
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        Cache::flush();
        
        parent::tearDown();
    }

    /**
     * Set up test-specific environment
     */
    protected function setUpTestEnvironment(): void
    {
        // Set consistent timezone
        config(['app.timezone' => 'UTC']);
        
        // Disable broadcasting in tests
        config(['broadcasting.default' => 'log']);
        
        // Use array cache driver
        config(['cache.default' => 'array']);
        
        // Use sync queue driver
        config(['queue.default' => 'sync']);
        
        // Use array mail driver
        config(['mail.default' => 'array']);
        
        // Disable rate limiting in tests
        config(['app.rate_limiting_enabled' => false]);
    }

    /**
     * Create a test user if needed
     */
    protected function createTestUser(array $attributes = [])
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Assert that the response is a successful API response
     */
    protected function assertApiSuccess($response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id'
                 ])
                 ->assertJson(['success' => true]);

        // Verify timestamp format
        $timestamp = $response->json('timestamp');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}000Z$/',
            $timestamp
        );

        // Verify request_id exists
        $this->assertNotEmpty($response->json('request_id'));
    }

    /**
     * Assert that the response is an API error response
     */
    protected function assertApiError($response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'request_id'
                 ])
                 ->assertJson(['success' => false]);

        $this->assertNotEmpty($response->json('message'));
    }

    /**
     * Assert that the response has standard pagination structure
     */
    protected function assertHasPagination($response): void
    {
        $response->assertJsonStructure([
            'data' => [
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'has_more_pages'
                ]
            ]
        ]);
    }

    /**
     * Measure execution time of a callback
     */
    protected function measureExecutionTime(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        return [
            'result' => $result,
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'memory_used_bytes' => $endMemory - $startMemory,
            'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024
        ];
    }

    /**
     * Assert that execution time is within acceptable limits
     */
    protected function assertExecutionTime(callable $callback, int $maxTimeMs): void
    {
        $metrics = $this->measureExecutionTime($callback);
        
        $this->assertLessThan(
            $maxTimeMs,
            $metrics['execution_time_ms'],
            "Execution took {$metrics['execution_time_ms']}ms, expected less than {$maxTimeMs}ms"
        );
    }

    /**
     * Assert that memory usage is within acceptable limits
     */
    protected function assertMemoryUsage(callable $callback, int $maxMemoryBytes): void
    {
        $metrics = $this->measureExecutionTime($callback);
        
        $this->assertLessThan(
            $maxMemoryBytes,
            $metrics['memory_used_bytes'],
            "Memory usage was {$metrics['memory_used_bytes']} bytes, expected less than {$maxMemoryBytes} bytes"
        );
    }

    /**
     * Count database queries executed during callback
     */
    protected function countDatabaseQueries(callable $callback): array
    {
        DB::enableQueryLog();
        
        $result = $callback();
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        DB::flushQueryLog();
        DB::disableQueryLog();

        return [
            'result' => $result,
            'query_count' => $queryCount,
            'queries' => $queries
        ];
    }

    /**
     * Assert that the number of database queries is within acceptable limits
     */
    protected function assertQueryCount(callable $callback, int $maxQueries): void
    {
        $metrics = $this->countDatabaseQueries($callback);
        
        $this->assertLessThanOrEqual(
            $maxQueries,
            $metrics['query_count'],
            "Executed {$metrics['query_count']} queries, expected {$maxQueries} or fewer"
        );
    }

    /**
     * Create test data using factories or helpers
     */
    protected function createTestData(): array
    {
        if (function_exists('seed_test_data')) {
            return seed_test_data();
        }

        // Fallback basic test data creation
        return [
            'message' => 'Test helpers not available, using fallback data creation'
        ];
    }

    /**
     * Mock external API calls if needed
     */
    protected function mockExternalApis(): void
    {
        // Override in test classes that need to mock external APIs
    }

    /**
     * Set up authentication for API tests
     */
    protected function authenticateApi($user = null): void
    {
        // If your API requires authentication, implement here
        // Example: $this->actingAs($user, 'api');
    }

    /**
     * Assert that a model exists in the database
     */
    protected function assertModelExists(string $model, array $attributes): void
    {
        $this->assertDatabaseHas((new $model)->getTable(), $attributes);
    }

    /**
     * Assert that a model does not exist in the database
     */
    protected function assertModelMissing(string $model, array $attributes): void
    {
        $this->assertDatabaseMissing((new $model)->getTable(), $attributes);
    }

    /**
     * Create a mock HTTP request for testing
     */
    protected function createMockRequest(array $data = [], array $headers = []): \Illuminate\Http\Request
    {
        $request = new \Illuminate\Http\Request($data);
        
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        
        return $request;
    }

    /**
     * Simulate a slow operation for performance testing
     */
    protected function simulateSlowOperation(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
