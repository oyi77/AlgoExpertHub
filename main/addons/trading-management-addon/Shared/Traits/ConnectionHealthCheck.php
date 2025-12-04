<?php

namespace Addons\TradingManagement\Shared\Traits;

/**
 * Trait for connection health checking and monitoring
 * 
 * Usage:
 * - Add this trait to connection models (DataConnection, ExecutionConnection)
 * - Model must have: status, last_tested_at, last_error, last_connected_at columns
 */
trait ConnectionHealthCheck
{
    /**
     * Mark connection as active (successful test/operation)
     * 
     * @return void
     */
    public function markAsActive(): void
    {
        $this->forceFill([
            'status' => 'active',
            'last_error' => null,
            'last_tested_at' => now(),
        ])->save();
    }

    /**
     * Mark connection as error
     * 
     * @param string|null $message Error message
     * @return void
     */
    public function markAsError(?string $message = null): void
    {
        $this->forceFill([
            'status' => 'error',
            'last_error' => $message,
            'last_tested_at' => now(),
        ])->save();
    }

    /**
     * Update last connected timestamp
     * 
     * @return void
     */
    public function updateLastConnected(): void
    {
        $this->forceFill([
            'last_connected_at' => now(),
        ])->save();
    }

    /**
     * Update last used timestamp
     * 
     * @return void
     */
    public function updateLastUsed(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    /**
     * Check if connection is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active == true;
    }

    /**
     * Check if connection has error
     * 
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Check if connection is stale (not tested recently)
     * 
     * @param int $hours Hours threshold (default: 24)
     * @return bool
     */
    public function isStale(int $hours = 24): bool
    {
        if (!$this->last_tested_at) {
            return true;
        }

        return $this->last_tested_at->lt(now()->subHours($hours));
    }

    /**
     * Get health status
     * 
     * @return array ['status' => string, 'message' => string, 'last_checked' => string]
     */
    public function getHealthStatus(): array
    {
        if ($this->hasError()) {
            return [
                'status' => 'error',
                'message' => $this->last_error ?? 'Unknown error',
                'last_checked' => $this->last_tested_at?->diffForHumans() ?? 'Never',
            ];
        }

        if ($this->isStale()) {
            return [
                'status' => 'stale',
                'message' => 'Connection not tested recently',
                'last_checked' => $this->last_tested_at?->diffForHumans() ?? 'Never',
            ];
        }

        if ($this->isActive()) {
            return [
                'status' => 'healthy',
                'message' => 'Connection working properly',
                'last_checked' => $this->last_tested_at->diffForHumans(),
            ];
        }

        return [
            'status' => 'inactive',
            'message' => 'Connection is inactive',
            'last_checked' => $this->last_tested_at?->diffForHumans() ?? 'Never',
        ];
    }

    /**
     * Scope: Only active connections
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('is_active', true);
    }

    /**
     * Scope: Only connections with errors
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope: Stale connections
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours Hours threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStale($query, int $hours = 24)
    {
        return $query->where(function($q) use ($hours) {
            $q->whereNull('last_tested_at')
              ->orWhere('last_tested_at', '<', now()->subHours($hours));
        });
    }
}

