<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataLoadingOptimizationService extends BaseService
{
    /**
     * Create optimized pagination with caching.
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $options
     * @return LengthAwarePaginator
     */
    public function createOptimizedPagination(Builder $query, int $perPage = 20, array $options = []): LengthAwarePaginator
    {
        $cacheKey = $options['cache_key'] ?? null;
        $cacheTtl = $options['cache_ttl'] ?? 300; // 5 minutes default
        $page = request()->get('page', 1);
        
        // Optimize query with select only needed columns
        if (isset($options['select'])) {
            $query->select($options['select']);
        }
        
        // Add eager loading to prevent N+1 queries
        if (isset($options['with'])) {
            $query->with($options['with']);
        }
        
        // Use cursor pagination for large datasets
        if ($options['use_cursor'] ?? false) {
            return $this->createCursorPagination($query, $perPage, $options);
        }
        
        // Cache total count for expensive queries
        $totalCount = null;
        if ($cacheKey && $cacheTtl > 0) {
            $countCacheKey = $cacheKey . '_count';
            $totalCount = Cache::remember($countCacheKey, $cacheTtl, function () use ($query) {
                return $query->count();
            });
        }
        
        // Create paginator
        if ($totalCount !== null) {
            $items = $query->forPage($page, $perPage)->get();
            return new LengthAwarePaginator(
                $items,
                $totalCount,
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }
        
        return $query->paginate($perPage);
    }

    /**
     * Create cursor-based pagination for large datasets.
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $options
     * @return array
     */
    public function createCursorPagination(Builder $query, int $perPage = 20, array $options = []): array
    {
        $cursorColumn = $options['cursor_column'] ?? 'id';
        $cursor = request()->get('cursor');
        $direction = request()->get('direction', 'next'); // next or prev
        
        if ($cursor) {
            if ($direction === 'next') {
                $query->where($cursorColumn, '>', $cursor);
            } else {
                $query->where($cursorColumn, '<', $cursor);
                $query->orderBy($cursorColumn, 'desc');
            }
        }
        
        // Get one extra item to determine if there are more pages
        $items = $query->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;
        
        if ($hasMore) {
            $items = $items->take($perPage);
        }
        
        // If we were going backwards, reverse the order
        if ($direction === 'prev' && $cursor) {
            $items = $items->reverse()->values();
        }
        
        $nextCursor = $hasMore && $items->isNotEmpty() ? $items->last()->{$cursorColumn} : null;
        $prevCursor = $items->isNotEmpty() ? $items->first()->{$cursorColumn} : null;
        
        return [
            'data' => $items,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
            'per_page' => $perPage,
        ];
    }

    /**
     * Implement infinite scroll pagination.
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $options
     * @return array
     */
    public function createInfiniteScrollPagination(Builder $query, int $perPage = 20, array $options = []): array
    {
        $page = (int) request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // Add eager loading
        if (isset($options['with'])) {
            $query->with($options['with']);
        }
        
        // Get items with one extra to check if there are more
        $items = $query->offset($offset)->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;
        
        if ($hasMore) {
            $items = $items->take($perPage);
        }
        
        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'has_more_pages' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        ];
    }

    /**
     * Create lazy loading configuration for images and content.
     *
     * @param array $options
     * @return array
     */
    public function createLazyLoadingConfig(array $options = []): array
    {
        return [
            'images' => [
                'enabled' => $options['images_enabled'] ?? true,
                'threshold' => $options['images_threshold'] ?? '50px',
                'placeholder' => $options['images_placeholder'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkxvYWRpbmcuLi48L3RleHQ+PC9zdmc+',
                'error_placeholder' => $options['images_error'] ?? '/images/placeholder-error.svg',
                'fade_in_duration' => $options['images_fade_duration'] ?? 300,
            ],
            'content' => [
                'enabled' => $options['content_enabled'] ?? true,
                'threshold' => $options['content_threshold'] ?? '100px',
                'skeleton_enabled' => $options['skeleton_enabled'] ?? true,
                'skeleton_lines' => $options['skeleton_lines'] ?? 3,
            ],
            'intersection_observer' => [
                'root_margin' => $options['root_margin'] ?? '50px',
                'threshold' => $options['observer_threshold'] ?? 0.1,
            ],
        ];
    }

    /**
     * Optimize database queries for large datasets.
     *
     * @param Builder $query
     * @param array $options
     * @return Builder
     */
    public function optimizeQuery(Builder $query, array $options = []): Builder
    {
        // Select only necessary columns
        if (isset($options['select'])) {
            $query->select($options['select']);
        }
        
        // Add proper indexes hint
        if (isset($options['use_index'])) {
            $query->from(DB::raw($query->getModel()->getTable() . ' USE INDEX (' . $options['use_index'] . ')'));
        }
        
        // Limit joins and use eager loading instead
        if (isset($options['with'])) {
            $query->with($options['with']);
        }
        
        // Add query caching for expensive operations
        if (isset($options['cache_key']) && isset($options['cache_ttl'])) {
            $cacheKey = $options['cache_key'];
            $cacheTtl = $options['cache_ttl'];
            
            return Cache::remember($cacheKey, $cacheTtl, function () use ($query) {
                return $query;
            });
        }
        
        return $query;
    }

    /**
     * Create data virtualization for very large lists.
     *
     * @param Builder $query
     * @param array $options
     * @return array
     */
    public function createVirtualizedData(Builder $query, array $options = []): array
    {
        $itemHeight = $options['item_height'] ?? 60; // pixels
        $containerHeight = $options['container_height'] ?? 400; // pixels
        $overscan = $options['overscan'] ?? 5; // extra items to render
        
        $visibleCount = (int) ceil($containerHeight / $itemHeight);
        $startIndex = max(0, (int) request()->get('start_index', 0));
        $endIndex = min($startIndex + $visibleCount + $overscan, $query->count());
        
        // Get total count for virtual scrolling
        $totalCount = $query->count();
        
        // Get only visible items
        $items = $query->offset($startIndex)->limit($endIndex - $startIndex)->get();
        
        return [
            'items' => $items,
            'total_count' => $totalCount,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
            'visible_count' => $visibleCount,
            'item_height' => $itemHeight,
            'total_height' => $totalCount * $itemHeight,
        ];
    }

    /**
     * Create progressive loading strategy.
     *
     * @param array $dataSources
     * @param array $options
     * @return array
     */
    public function createProgressiveLoading(array $dataSources, array $options = []): array
    {
        $priority = $options['priority'] ?? 'high'; // high, medium, low
        $loadingStrategy = [];
        
        foreach ($dataSources as $key => $source) {
            $loadingStrategy[$key] = [
                'priority' => $source['priority'] ?? $priority,
                'cache_key' => $source['cache_key'] ?? null,
                'cache_ttl' => $source['cache_ttl'] ?? 300,
                'lazy_load' => $source['lazy_load'] ?? false,
                'preload' => $source['preload'] ?? false,
                'critical' => $source['critical'] ?? false,
            ];
        }
        
        // Sort by priority
        uasort($loadingStrategy, function ($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorities[$b['priority']] <=> $priorities[$a['priority']];
        });
        
        return $loadingStrategy;
    }

    /**
     * Get performance metrics for data loading.
     *
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'query_count' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
            'cache_stats' => [
                'hits' => Cache::getStore()->getHits ?? 0,
                'misses' => Cache::getStore()->getMisses ?? 0,
            ],
            'response_time' => microtime(true) - LARAVEL_START,
        ];
    }

    /**
     * Create skeleton loading placeholders.
     *
     * @param array $options
     * @return array
     */
    public function createSkeletonPlaceholders(array $options = []): array
    {
        return [
            'card' => [
                'lines' => $options['card_lines'] ?? 3,
                'image' => $options['card_image'] ?? true,
                'width_variations' => $options['width_variations'] ?? [100, 80, 60],
            ],
            'list_item' => [
                'lines' => $options['list_lines'] ?? 2,
                'avatar' => $options['list_avatar'] ?? false,
                'width_variations' => $options['width_variations'] ?? [100, 70],
            ],
            'table_row' => [
                'columns' => $options['table_columns'] ?? 4,
                'width_variations' => $options['width_variations'] ?? [100, 80, 60, 90],
            ],
            'animation' => [
                'type' => $options['animation_type'] ?? 'pulse', // pulse, wave, shimmer
                'duration' => $options['animation_duration'] ?? 1500,
                'delay' => $options['animation_delay'] ?? 100,
            ],
        ];
    }
}