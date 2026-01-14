<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com https://s3.tradingview.com https://static.cloudflareinsights.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://maxst.icons8.com; " .
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://maxst.icons8.com https://cdnjs.cloudflare.com data:; " .
            "img-src 'self' data: blob: https:; " .
            "frame-src 'self' https://s.tradingview.com https://*.tradingview.com; " .
            "connect-src 'self' https://api.tradingview.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.shopimgs.com wss://stream.binance.com wss://*.binance.com https://stream.binance.com https://*.binance.com https://cloudflareinsights.com;";
        
        $response->headers->set('Content-Security-Policy', $csp);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $response->headers->set('X-XSS-Protection', '1; mode=block');

        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('Permissions-Policy', 
            'geolocation=(), microphone=(), camera=()'
        );

        return $response;
    }
}
