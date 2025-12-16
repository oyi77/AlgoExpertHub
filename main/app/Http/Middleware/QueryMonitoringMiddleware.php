<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\DB;

class QueryMonitoringMiddleware
{
    protected $queryOptimizationService;

    public function __construct(QueryOptimizationService $queryOptimizationService)
    {
        $this->queryOptimizationService = $queryOptimizationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Enable query logging only in debug mode or for specific routes
        if (config('app.debug') || $request->has('debug_queries')) {
            DB::enableQueryLog();
            $this->queryOptimizationService->enableQueryMonitoring();
        }

        $response = $next($request);

        // Add query stats to response headers in debug mode
        if (config('app.debug') && $request->has('debug_queries')) {
            $stats = $this->queryOptimizationService->getQueryStats();
            $response->headers->set('X-Query-Count', $stats['total_queries']);
            $response->headers->set('X-Query-Time', round($stats['total_time'], 2) . 'ms');
            $response->headers->set('X-Slow-Queries', $stats['slow_queries']);
        }

        return $response;
    }
}