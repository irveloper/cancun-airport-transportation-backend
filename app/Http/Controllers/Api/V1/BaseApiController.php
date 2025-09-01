<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;

class BaseApiController extends Controller
{
    /**
     * Standard success response format
     */
    protected function successResponse($data = null, string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? __('api.success'),
            'timestamp' => now()->toISOString(),
            'request_id' => request()->id ?? uniqid(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Standard error response format
     */
    protected function errorResponse(string $message = null, int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message ?? __('api.error'),
            'timestamp' => now()->toISOString(),
            'request_id' => request()->id ?? uniqid(),
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse(__('api.validation_failed'), 422, $errors);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $resource = null): JsonResponse
    {
        $message = $resource ? __('api.resources.' . strtolower($resource) . '.not_found') : __('api.not_found');
        return $this->errorResponse($message, 404);
    }

    /**
     * Internal server error response
     */
    protected function serverErrorResponse(string $message = null): JsonResponse
    {
        return $this->errorResponse($message ?? __('api.internal_server_error'), 500);
    }

    /**
     * Created response
     */
    protected function createdResponse($data, string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message ?? __('api.created'), 201);
    }

    /**
     * No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Resource-specific success response
     */
    protected function resourceResponse(string $resource, string $action, $data = null, int $status = 200): JsonResponse
    {
        $message = __("api.resources.{$resource}.{$action}");
        return $this->successResponse($data, $message, $status);
    }

    /**
     * Resource-specific error response
     */
    protected function resourceErrorResponse(string $resource, string $error, int $status = 400): JsonResponse
    {
        $message = __("api.resources.{$resource}.{$error}");
        return $this->errorResponse($message, $status);
    }

    /**
     * Log errors with context for monitoring and debugging
     */
    protected function logError(string $message, Throwable $exception, array $context = []): void
    {
        $logData = [
            'message' => $message,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'request' => [
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ],
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::error('API Error: ' . $message, $logData);

        // Send to monitoring service if configured
        if (config('app.env') === 'production') {
            $this->sendToMonitoring($logData);
        }
    }

    /**
     * Cache frequently accessed data with proper TTL
     */
    protected function cacheData(string $key, $data, int $ttl = 3600): void
    {
        $cacheKey = "api_v1_{$key}";
        Cache::put($cacheKey, $data, $ttl);
    }

    /**
     * Get cached data with fallback
     */
    protected function getCachedData(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = "api_v1_{$key}";

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear specific cache keys
     */
    protected function clearCache(string $pattern): void
    {
        $keys = Cache::get('api_v1_' . $pattern);
        if ($keys) {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Monitor database performance
     */
    protected function monitorQueryPerformance(callable $callback, string $operation = 'query'): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $result = $callback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsed = $endMemory - $startMemory;

            // Log slow queries
            if ($executionTime > 1000) { // Log queries taking more than 1 second
                Log::warning('Slow API Query', [
                    'operation' => $operation,
                    'execution_time_ms' => $executionTime,
                    'memory_used_bytes' => $memoryUsed,
                    'url' => request()->fullUrl(),
                ]);
            }

            return $result;
        } catch (Exception $e) {
            $this->logError("Database operation failed: {$operation}", $e);
            throw $e;
        }
    }

    /**
     * Validate and sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }, $data);
    }

    /**
     * Send error data to monitoring service (e.g., Sentry, Bugsnag)
     */
    private function sendToMonitoring(array $logData): void
    {
        // Implement your monitoring service integration here
        // Example: Sentry, Bugsnag, New Relic, etc.

        if (class_exists('\Sentry\SentrySdk')) {
            \Sentry\SentrySdk::getCurrentHub()->captureException(
                new Exception($logData['message']),
                \Sentry\State\Scope::create()->setContext('api_error', $logData)
            );
        }
    }

    /**
     * Get pagination parameters with limits
     */
    protected function getPaginationParams(): array
    {
        $perPage = min(request()->input('per_page', 15), 100); // Max 100 items per page
        $page = max(request()->input('page', 1), 1); // Min page 1

        return [
            'per_page' => $perPage,
            'page' => $page,
        ];
    }

    /**
     * Validate date range for queries
     */
    protected function validateDateRange(?string $startDate, ?string $endDate): array
    {
        $errors = [];

        if ($startDate && !strtotime($startDate)) {
            $errors[] = 'Invalid start date format';
        }

        if ($endDate && !strtotime($endDate)) {
            $errors[] = 'Invalid end date format';
        }

        if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
            $errors[] = 'Start date cannot be after end date';
        }

        return $errors;
    }
}
