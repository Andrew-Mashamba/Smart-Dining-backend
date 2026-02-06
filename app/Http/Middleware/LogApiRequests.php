<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the API request details
        Log::info('API Request', [
            'method' => $request->method(),
            'endpoint' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'ip_address' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
            'user_agent' => $request->userAgent(),
        ]);

        $response = $next($request);

        // Log the response status
        Log::info('API Response', [
            'method' => $request->method(),
            'endpoint' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return $response;
    }
}
