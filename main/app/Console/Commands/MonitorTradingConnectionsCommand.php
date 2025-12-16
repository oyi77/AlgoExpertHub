<?php

namespace App\Console\Commands;

use App\Services\Trading\ConnectionHealthMonitor;
use App\Models\TradingConnection;
use Illuminate\Console\Command;

class MonitorTradingConnectionsCommand extends Command
{
    protected $signature = 'trading:monitor-connections';
    protected $description = 'Monitor trading connection health and perform failover if needed';

    protected ConnectionHealthMonitor $monitor;

    public function __construct(ConnectionHealthMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle(): int
    {
        $this->info('Monitoring trading connections...');

        $connections = TradingConnection::where('is_active', true)->get();

        foreach ($connections as $connection) {
            $health = $this->monitor->checkHealth($connection);

            if ($health['is_healthy']) {
                $this->info("Connection {$connection->id} is healthy");
            } else {
                $this->warn("Connection {$connection->id} is unhealthy:");
                foreach ($health['issues'] as $issue) {
                    $this->line("  - {$issue}");
                }

                // Attempt recovery
                $this->info("Attempting recovery...");
                if ($this->monitor->attemptRecovery($connection)) {
                    $this->info("Recovery successful");
                } else {
                    $this->warn("Recovery failed, attempting failover...");
                    $backup = $this->monitor->failover($connection);
                    if ($backup) {
                        $this->info("Failover successful to connection {$backup->id}");
                    } else {
                        $this->error("Failover failed - no backup available");
                    }
                }
            }
        }

        return 0;
    }
}
