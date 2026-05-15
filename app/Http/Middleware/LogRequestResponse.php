<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::channel('single')->info('API request', [
            'method'      => $request->method(),
            'path'        => $request->path(),
            'ip'          => $request->ip(),
            'user_id'     => auth('api')->id(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
