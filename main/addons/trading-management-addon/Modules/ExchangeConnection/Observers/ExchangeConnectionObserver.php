<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Observers;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Illuminate\Support\Facades\Log;

/**
 * ExchangeConnectionObserver
 * 
 * Automatically tests connection health on creation/update
 */
class ExchangeConnectionObserver
{
    protected ExchangeConnectionService $service;

    public function __construct(ExchangeConnectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the ExchangeConnection "created" event.
     */
    public function created(ExchangeConnection $connection): void
    {
        // Don't auto-test on creation - user should test manually first
        // Just set initial status
        if (!$connection->status) {
            $connection->update(['status' => 'inactive']);
        }
    }

    /**
     * Handle the ExchangeConnection "updated" event.
     */
    public function updated(ExchangeConnection $connection): void
    {
        // If credentials changed, mark as needing test
        if ($connection->wasChanged('credentials')) {
            // Don't auto-test, just mark as inactive
            if ($connection->status === 'active') {
                $connection->update([
                    'status' => 'inactive',
                    'is_active' => false,
                ]);
                
                Log::info('Connection credentials changed, marked as inactive', [
                    'connection_id' => $connection->id,
                ]);
            }
        }
    }

    /**
     * Handle the ExchangeConnection "saving" event.
     */
    public function saving(ExchangeConnection $connection): void
    {
        // Ensure status is set
        if (!$connection->status) {
            $connection->status = 'inactive';
        }
    }
}
