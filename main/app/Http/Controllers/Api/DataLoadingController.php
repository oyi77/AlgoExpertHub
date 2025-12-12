<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\SignalResource;
use App\Models\Signal;
use App\Services\DataLoadingOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DataLoadingController extends BaseApiController
{
    protected DataLoadingOptimizationService $dataLoadingService;

    public function __construct(DataLoadingOptimizationService $dataLoadingService)
    {
        $this->dataLoadingService = $dataLoadingService;
    }

    /**
     * Get signals with infinite scroll pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSignalsInfiniteScroll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = min((int) $request->get('per_page', 20), 50);
            
            $query = Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->with(['pair', 'time', 'market'])
                ->orderBy('published_date', 'desc');

            // Apply filters
            $this->applySignalFilters($query, $request);

            $result = $this->dataLoadingService->createInfiniteScrollPagination($query, $perPage);

            return $this->successResponse([
                'signals' => SignalResource::collection($result['data']),
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'per_page' => $result['per_page'],
                    'has_more_pages' => $result['has_more_pages'],
                    'next_page' => $result['next_page'],
                ],
                'performance' => $this->dataLoadingService->getPerformanceMetrics(),
            ], 'Signals loaded successfully');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get signals with cursor pagination for large datasets.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSignalsCursorPagination(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = min((int) $request->get('per_page', 20), 50);
            
            $query = Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->with(['pair', 'time', 'market'])
                ->orderBy('id', 'desc');

            // Apply filters
            $this->applySignalFilters($query, $request);

            $result = $this->dataLoadingService->createCursorPagination($query, $perPage, [
                'cursor_column' => 'id'
            ]);

            return $this->successResponse([
                'signals' => SignalResource::collection($result['data']),
                'pagination' => [
                    'has_more' => $result['has_more'],
                    'next_cursor' => $result['next_cursor'],
                    'prev_cursor' => $result['prev_cursor'],
                    'per_page' => $result['per_page'],
                ],
                'performance' => $this->dataLoadingService->getPerformanceMetrics(),
            ], 'Signals loaded successfully');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get virtualized data for very large lists.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVirtualizedSignals(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $query = Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->with(['pair', 'time', 'market'])
                ->orderBy('published_date', 'desc');

            // Apply filters
            $this->applySignalFilters($query, $request);

            $result = $this->dataLoadingService->createVirtualizedData($query, [
                'item_height' => (int) $request->get('item_height', 80),
                'container_height' => (int) $request->get('container_height', 600),
                'overscan' => (int) $request->get('overscan', 5),
            ]);

            return $this->successResponse([
                'signals' => SignalResource::collection($result['items']),
                'virtualization' => [
                    'total_count' => $result['total_count'],
                    'start_index' => $result['start_index'],
                    'end_index' => $result['end_index'],
                    'visible_count' => $result['visible_count'],
                    'item_height' => $result['item_height'],
                    'total_height' => $result['total_height'],
                ],
                'performance' => $this->dataLoadingService->getPerformanceMetrics(),
            ], 'Virtualized signals loaded successfully');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get lazy loading configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLazyLoadingConfig(Request $request): JsonResponse
    {
        $config = $this->dataLoadingService->createLazyLoadingConfig([
            'images_enabled' => $request->boolean('images_enabled', true),
            'content_enabled' => $request->boolean('content_enabled', true),
            'skeleton_enabled' => $request->boolean('skeleton_enabled', true),
        ]);

        return $this->successResponse($config, 'Lazy loading configuration retrieved');
    }

    /**
     * Get skeleton placeholders configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkeletonPlaceholders(Request $request): JsonResponse
    {
        $type = $request->get('type', 'card'); // card, list_item, table_row
        
        $placeholders = $this->dataLoadingService->createSkeletonPlaceholders([
            'card_lines' => (int) $request->get('card_lines', 3),
            'list_lines' => (int) $request->get('list_lines', 2),
            'table_columns' => (int) $request->get('table_columns', 4),
            'animation_type' => $request->get('animation_type', 'pulse'),
        ]);

        return $this->successResponse([
            'type' => $type,
            'config' => $placeholders[$type] ?? $placeholders['card'],
            'all_types' => $placeholders,
        ], 'Skeleton placeholders configuration retrieved');
    }

    /**
     * Get performance metrics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        $metrics = $this->dataLoadingService->getPerformanceMetrics();
        
        return $this->successResponse($metrics, 'Performance metrics retrieved');
    }

    /**
     * Preload critical data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function preloadCriticalData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dataSources = [
                'recent_signals' => [
                    'priority' => 'high',
                    'critical' => true,
                    'cache_key' => "user_{$user->id}_recent_signals",
                    'cache_ttl' => 300,
                ],
                'user_stats' => [
                    'priority' => 'high',
                    'critical' => true,
                    'cache_key' => "user_{$user->id}_stats",
                    'cache_ttl' => 600,
                ],
                'market_data' => [
                    'priority' => 'medium',
                    'critical' => false,
                    'cache_key' => 'market_data',
                    'cache_ttl' => 900,
                ],
            ];

            $loadingStrategy = $this->dataLoadingService->createProgressiveLoading($dataSources);

            // Load critical data first
            $criticalData = [];
            foreach ($loadingStrategy as $key => $config) {
                if ($config['critical']) {
                    switch ($key) {
                        case 'recent_signals':
                            $criticalData[$key] = $this->getRecentSignalsData($user);
                            break;
                        case 'user_stats':
                            $criticalData[$key] = $this->getUserStatsData($user);
                            break;
                    }
                }
            }

            return $this->successResponse([
                'critical_data' => $criticalData,
                'loading_strategy' => $loadingStrategy,
                'performance' => $this->dataLoadingService->getPerformanceMetrics(),
            ], 'Critical data preloaded successfully');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Apply signal filters to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     */
    private function applySignalFilters($query, Request $request): void
    {
        if ($request->has('market_id')) {
            $query->where('market_id', $request->get('market_id'));
        }

        if ($request->has('currency_pair_id')) {
            $query->where('currency_pair_id', $request->get('currency_pair_id'));
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->get('direction'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('published_date', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('published_date', '<=', $request->get('date_to'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
    }

    /**
     * Get recent signals data.
     *
     * @param \App\Models\User $user
     * @return array
     */
    private function getRecentSignalsData($user): array
    {
        $signals = Signal::published()
            ->whereHas('plans.subscriptions', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('is_current', 1)
                  ->where('status', 'active');
            })
            ->with(['pair', 'time', 'market'])
            ->orderBy('published_date', 'desc')
            ->limit(5)
            ->get();

        return [
            'signals' => SignalResource::collection($signals),
            'count' => $signals->count(),
        ];
    }

    /**
     * Get user stats data.
     *
     * @param \App\Models\User $user
     * @return array
     */
    private function getUserStatsData($user): array
    {
        return [
            'total_signals' => Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->count(),
            'signals_this_month' => Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->whereMonth('published_date', now()->month)
                ->whereYear('published_date', now()->year)
                ->count(),
            'current_plan' => $user->currentplan->first()?->plan->name ?? 'No active plan',
        ];
    }
}