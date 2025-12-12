<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class BaseApiController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiResponseTrait;

    /**
     * Default pagination limit.
     *
     * @var int
     */
    protected $defaultLimit = 20;

    /**
     * Maximum pagination limit.
     *
     * @var int
     */
    protected $maxLimit = 100;

    /**
     * Get pagination limit from request.
     *
     * @param \Illuminate\Http\Request $request
     * @return int
     */
    protected function getPaginationLimit($request): int
    {
        $limit = (int) $request->get('limit', $this->defaultLimit);
        
        return min($limit, $this->maxLimit);
    }

    /**
     * Get sort parameters from request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return array
     */
    protected function getSortParameters($request, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): array
    {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);
        
        // Validate direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }
        
        return [$sort, $direction];
    }

    /**
     * Get filter parameters from request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $allowedFilters
     * @return array
     */
    protected function getFilterParameters($request, array $allowedFilters = []): array
    {
        $filters = [];
        
        foreach ($allowedFilters as $filter) {
            if ($request->has($filter) && $request->get($filter) !== null) {
                $filters[$filter] = $request->get($filter);
            }
        }
        
        return $filters;
    }

    /**
     * Apply filters to query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query;
    }

    /**
     * Apply search to query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param array $searchFields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySearch($query, string $search, array $searchFields = [])
    {
        if (empty($search) || empty($searchFields)) {
            return $query;
        }
        
        return $query->where(function ($q) use ($search, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Handle common API exceptions.
     *
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Exception $e)
    {
        if (config('app.debug')) {
            return $this->serverErrorResponse($e->getMessage());
        }
        
        return $this->serverErrorResponse('An error occurred while processing your request');
    }
}