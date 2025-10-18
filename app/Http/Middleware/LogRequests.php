<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // تسجيل الطلب القادم
        if ($request->path() === 'api/pi/auth') {
            Log::channel('single')->info('>>> REQUEST TO /api/pi/auth', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'body_length' => strlen($request->getContent()),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        $response = $next($request);

        // تسجيل الرد
        if ($request->path() === 'api/pi/auth') {
            Log::channel('single')->info('<<< RESPONSE FROM /api/pi/auth', [
                'status' => $response->getStatusCode(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return $response;
    }
}
