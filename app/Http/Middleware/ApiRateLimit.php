<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Different rate limits for different endpoints
        $limits = $this->getRateLimits($request);
        
        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($key . ':' . $limit['name'], $limit['max_attempts'])) {
                return $this->buildResponse($key, $limit['name']);
            }
            
            RateLimiter::hit($key . ':' . $limit['name'], $limit['decay_minutes'] * 60);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $limits, $key
        );
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address as primary identifier
        $identifier = $request->ip();
        
        // If user is authenticated, use user ID
        if ($request->user()) {
            $identifier = 'user_' . $request->user()->id;
        }
        
        // Add API key if present
        if ($request->header('X-API-Key')) {
            $identifier .= '_' . $request->header('X-API-Key');
        }

        return sha1($identifier);
    }

    /**
     * Get rate limits based on endpoint
     */
    protected function getRateLimits(Request $request): array
    {
        $path = $request->path();
        
        // Default limits
        $defaultLimits = [
            [
                'name' => 'general',
                'max_attempts' => 60,
                'decay_minutes' => 1
            ]
        ];

        // Specific limits for different endpoints
        $specificLimits = [
            'api/v1/quote' => [
                [
                    'name' => 'quote',
                    'max_attempts' => 30,
                    'decay_minutes' => 1
                ]
            ],
            'api/v1/autocomplete' => [
                [
                    'name' => 'autocomplete',
                    'max_attempts' => 100,
                    'decay_minutes' => 1
                ]
            ],
            'api/v1/rates' => [
                [
                    'name' => 'rates_read',
                    'max_attempts' => 120,
                    'decay_minutes' => 1
                ]
            ]
        ];

        // Check for specific limits
        foreach ($specificLimits as $pattern => $limits) {
            if (str_contains($path, $pattern)) {
                return array_merge($defaultLimits, $limits);
            }
        }

        return $defaultLimits;
    }

    /**
     * Create a 'too many attempts' response
     */
    protected function buildResponse(string $key, string $limitName): JsonResponse
    {
        $retryAfter = RateLimiter::availableIn($key . ':' . $limitName);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString(),
        ], 429);
    }

    /**
     * Add the limit header information to the given response
     */
    protected function addHeaders($response, array $limits, string $key): Response
    {
        $headers = [];
        
        foreach ($limits as $limit) {
            $remaining = RateLimiter::remaining($key . ':' . $limit['name'], $limit['max_attempts']);
            $headers['X-RateLimit-' . ucfirst($limit['name']) . '-Remaining'] = $remaining;
            $headers['X-RateLimit-' . ucfirst($limit['name']) . '-Limit'] = $limit['max_attempts'];
        }

        return $response->withHeaders($headers);
    }
}
