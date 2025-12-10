<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'payment-success/*'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // For AJAX/JSON requests, return JSON error instead of redirecting
            if ($request->ajax() 
                || $request->expectsJson() 
                || $request->wantsJson()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->header('Accept') === 'application/json'
                || str_contains($request->header('Accept', ''), 'application/json')) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSRF token mismatch. Please refresh the page and try again.'
                ], 419);
            }
            throw $e;
        }
    }
}
