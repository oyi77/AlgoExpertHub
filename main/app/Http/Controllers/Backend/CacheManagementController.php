<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CacheManager;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;

class CacheManagementController extends Controller
{
    protected $cacheManager;
    protected $queryOptimizationService;

    public function __construct(CacheManager $cacheManager, QueryOptimizationService $queryOptimizationService)
    {
        $this->cacheManager = $cacheManager;
        $this->queryOptimizationService = $queryOptimizationService;
    }

    /**
     * Display cache management dashboard
     */
    public function index()
    {
        $cacheStats = $this->cacheManager->getStats();
        $cacheSize = $this->cacheManager->getCacheSize();
        $memoryStats = $this->cacheManager->getMemoryStats();
        $dbMetrics = $this->queryOptimizationService->getDatabaseMetrics();

        return view('backend.cache.index', compact(
            'cacheStats',
            'cacheSize', 
            'memoryStats',
            'dbMetrics'
        ));
    }

    /**
     * Warm cache
     */
    public function warm(Request $request)
    {
        try {
            $this->cacheManager->warmCache();
            
            return response()->json([
                'type' => 'success',
                'message' => 'Cache warmed successfully',
                'stats' => $this->cacheManager->getStats()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to warm cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache by tags
     */
    public function clearByTags(Request $request)
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'required|string'
        ]);

        try {
            $success = $this->cacheManager->invalidateByTags($request->tags);
            
            if ($success) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'Cache cleared for tags: ' . implode(', ', $request->tags)
                ]);
            } else {
                return response()->json([
                    'type' => 'error',
                    'message' => 'Failed to clear cache'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all cache
     */
    public function clearAll(Request $request)
    {
        try {
            $success = $this->cacheManager->clearAll();
            
            if ($success) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'All cache cleared successfully'
                ]);
            } else {
                return response()->json([
                    'type' => 'error',
                    'message' => 'Failed to clear all cache'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'cache' => $this->cacheManager->getStats(),
                'size' => $this->cacheManager->getCacheSize(),
                'memory' => $this->cacheManager->getMemoryStats(),
                'database' => $this->queryOptimizationService->getDatabaseMetrics()
            ];

            return response()->json([
                'type' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to get cache stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get query statistics
     */
    public function queryStats()
    {
        try {
            $stats = $this->queryOptimizationService->getQueryStats();

            return response()->json([
                'type' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to get query stats: ' . $e->getMessage()
            ], 500);
        }
    }
}