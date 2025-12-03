<?php

namespace Addons\AiConnectionAddon\App\Console\Commands;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Console\Command;

class MonitorConnectionHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-connections:monitor
                            {--provider= : Filter by provider slug}
                            {--test : Run connection tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor health of AI connections';

    /**
     * Execute the console command.
     */
    public function handle(AiConnectionService $connectionService)
    {
        $this->info('Monitoring AI Connections Health...');
        $this->newLine();

        $query = AiConnection::with('provider');

        if ($this->option('provider')) {
            $provider = $this->option('provider');
            $query->whereHas('provider', function ($q) use ($provider) {
                $q->where('slug', $provider);
            });
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->warn('No connections found.');
            return 0;
        }

        $headers = ['ID', 'Provider', 'Name', 'Status', 'Priority', 'Success Rate', 'Health', 'Last Used'];
        $rows = [];

        foreach ($connections as $connection) {
            $rows[] = [
                $connection->id,
                $connection->provider->name,
                $connection->name,
                $this->getStatusDisplay($connection->status),
                $connection->priority,
                number_format($connection->success_rate, 2) . '%',
                $this->getHealthDisplay($connection->health_status),
                $connection->last_used_at ? $connection->last_used_at->diffForHumans() : 'Never',
            ];
        }

        $this->table($headers, $rows);

        // Run tests if requested
        if ($this->option('test')) {
            $this->newLine();
            $this->info('Running connection tests...');
            
            foreach ($connections as $connection) {
                $this->info("Testing: {$connection->name}...");
                $result = $connectionService->testConnection($connection->id);
                
                if ($result['success']) {
                    $this->info("✓ {$result['message']} ({$result['response_time_ms']}ms)");
                } else {
                    $this->error("✗ {$result['message']}");
                }
            }
        }

        return 0;
    }

    protected function getStatusDisplay(string $status): string
    {
        return match ($status) {
            'active' => '<fg=green>Active</>',
            'inactive' => '<fg=yellow>Inactive</>',
            'error' => '<fg=red>Error</>',
            default => $status,
        };
    }

    protected function getHealthDisplay(string $health): string
    {
        return match ($health) {
            'healthy' => '<fg=green>Healthy</>',
            'degraded' => '<fg=yellow>Degraded</>',
            'warning' => '<fg=yellow>Warning</>',
            'critical' => '<fg=red>Critical</>',
            default => $health,
        };
    }
}

