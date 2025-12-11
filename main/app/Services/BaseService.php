<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class BaseService
{
    /**
     * Standard response format for all services
     */
    protected function successResponse(string $message, array $data = []): array
    {
        return [
            'type' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Standard error response format for all services
     */
    protected function errorResponse(string $message, array $errors = [], int $code = 400): array
    {
        return [
            'type' => 'error',
            'message' => $message,
            'errors' => $errors,
            'code' => $code
        ];
    }

    /**
     * Execute operation within database transaction
     */
    protected function executeInTransaction(callable $operation): array
    {
        try {
            return DB::transaction(function () use ($operation) {
                return $operation();
            });
        } catch (ValidationException $e) {
            Log::warning('Validation error in service operation', [
                'service' => static::class,
                'errors' => $e->errors()
            ]);
            
            return $this->errorResponse(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Service operation failed', [
                'service' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                'Operation failed: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Log service operation for debugging
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        Log::info('Service operation', [
            'service' => static::class,
            'operation' => $operation,
            'context' => $context
        ]);
    }

    /**
     * Validate required parameters
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data, array $allowedFields): array
    {
        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Handle pagination parameters
     */
    protected function getPaginationParams(array $params): array
    {
        return [
            'page' => max(1, (int) ($params['page'] ?? 1)),
            'per_page' => min(100, max(10, (int) ($params['per_page'] ?? 15))),
            'sort_by' => $params['sort_by'] ?? 'created_at',
            'sort_order' => in_array($params['sort_order'] ?? 'desc', ['asc', 'desc']) 
                ? $params['sort_order'] 
                : 'desc'
        ];
    }

    /**
     * Apply search filters to query
     */
    protected function applySearchFilters($query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }
            
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif (str_contains($value, '%')) {
                $query->where($field, 'like', $value);
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * Cache operation result
     */
    protected function cacheResult(string $key, callable $operation, int $ttl = 3600)
    {
        return cache()->remember($key, $ttl, $operation);
    }

    /**
     * Invalidate cache by pattern
     */
    protected function invalidateCache(array $tags): void
    {
        if (method_exists(cache(), 'tags')) {
            cache()->tags($tags)->flush();
        }
    }
}