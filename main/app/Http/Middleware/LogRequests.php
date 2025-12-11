<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Log incoming request
        Log::debug('Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id() ?? 'guest',
            'admin_id' => auth()->guard('admin')->id() ?? null,
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
            'input' => $this->sanitizeInput($request->all()),
        ]);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Log response
        Log::debug('Outgoing Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'content_type' => $response->headers->get('Content-Type'),
            'content_length' => strlen($response->getContent()),
        ]);

        return $response;
    }

    /**
     * Sanitize sensitive input data
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'credit_card', 'cvv'];
        
        foreach ($input as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $input[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $input[$key] = $this->sanitizeInput($value);
            }
        }

        return $input;
    }
}

