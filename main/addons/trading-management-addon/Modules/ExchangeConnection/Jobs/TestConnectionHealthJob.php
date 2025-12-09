<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Jobs;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TestConnectionHealthJob
 * 
 * Scheduled job to periodically test connection health
 */
class TestConnectionHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $connectionId;

    /**
     * Create a new job instance.
     * 
     * @param int|null $connectionId If null, tests all active connections
     */
    public function __construct(?int $connectionId = null)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * Execute the job.
     */
    public function handle(ExchangeConnectionService $service): void
    {
        if ($this->connectionId) {
            // Test specific connection
            $connection = ExchangeConnection::find($this->connectionId);
            if ($connection) {
                $this->testConnection($connection, $service);
            }
        } else {
            // Test all active connections that are stale (not tested in last 5 minutes)
            $connections = ExchangeConnection::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('last_tested_at')
                          ->orWhere('last_tested_at', '<', now()->subMinutes(5));
                })
                ->get();

            foreach ($connections as $connection) {
                $this->testConnection($connection, $service);
            }

            Log::info('Connection health check completed', [
                'connections_tested' => $connections->count(),
            ]);
        }
    }

    /**
     * Test a single connection
     */
    protected function testConnection(ExchangeConnection $connection, ExchangeConnectionService $service): void
    {
        try {
            $result = $service->testConnection($connection);
            
            if (!$result['success']) {
                Log::warning('Connection health check failed', [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'error' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Connection health check error', [
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
