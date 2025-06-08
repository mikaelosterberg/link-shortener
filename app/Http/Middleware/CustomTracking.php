<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Example Custom Tracking Middleware
 *
 * This is an example of how users can create custom middleware
 * for additional tracking or processing of requests.
 *
 * To use this middleware:
 * 1. Uncomment the registration in CustomizationServiceProvider
 * 2. Add your custom tracking logic in the handle method
 */
class CustomTracking
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Example: Log all incoming requests
        // logger('Request received', [
        //     'url' => $request->fullUrl(),
        //     'method' => $request->method(),
        //     'ip' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        // ]);

        $response = $next($request);

        // Example: Log response details
        // logger('Response sent', [
        //     'status' => $response->getStatusCode(),
        //     'url' => $request->fullUrl(),
        // ]);

        return $response;
    }
}
