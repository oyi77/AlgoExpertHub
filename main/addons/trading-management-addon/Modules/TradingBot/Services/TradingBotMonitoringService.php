<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TradingBotMonitoringService
 * 
 * Aggregates monitoring data for trading bots
 */
class TradingBotMonitoringService
{
    protected TradingBotWorkerService $workerService;

    public function __construct(TradingBotWorkerService $workerService)
    {
        $this->workerService = $workerService;
    }

    /**
     * Get worker status and metrics for a bot
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function getWorkerStatus(TradingBot $bot): array
    {
        $workerStatus = $this->workerService->getWorkerStatus($bot);
        $isRunning = $this->workerService->isWorkerRunning($bot);

        // Calculate uptime
        $uptime = null;
        if ($bot->worker_started_at && $isRunning) {
            $uptime = $bot->worker_started_at->diffForHumans();
        }

        // Get process info if running
        $processInfo = null;
        if ($bot->worker_pid && $isRunning) {
            try {
                $output = shell_exec("ps -p {$bot->worker_pid} -o pid,etime,stat,cmd --no-headers 2>&1");
                $processInfo = [
                    'pid' => $bot->worker_pid,
                    'info' => trim($output ?? ''),
                ];
            } catch (\Exception $e) {
                $processInfo = ['pid' => $bot->worker_pid, 'error' => $e->getMessage()];
            }
        }

        return [
            'status' => $workerStatus,
            'is_running' => $isRunning,
            'worker_pid' => $bot->worker_pid,
            'worker_started_at' => $bot->worker_started_at?->toIso8601String(),
            'uptime' => $uptime,
            'process_info' => $processInfo,
        ];
    }

    /**
     * Get bot metrics
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function getBotMetrics(TradingBot $bot): array
    {
        // Check if trading_bot_positions table exists
        $positionsTableExists = Schema::hasTable('trading_bot_positions');
        
        // Get last signal processed (from positions or execution logs)
        $lastSignalProcessed = null;
        $signalsProcessed = 0;
        
        if ($positionsTableExists) {
            try {
                $lastSignalProcessed = TradingBotPosition::where('bot_id', $bot->id)
                    ->whereNotNull('signal_id')
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Count signals processed (positions with signal_id)
                $signalsProcessed = TradingBotPosition::where('bot_id', $bot->id)
                    ->whereNotNull('signal_id')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to query trading_bot_positions', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Count errors in last 24 hours
        $errorCount = $this->getErrorCount($bot->id, 24);

        // Get worker restart count (from execution logs)
        $restartCount = 0;
        if (Schema::hasTable('trading_bot_execution_logs')) {
            try {
                $restartCount = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotExecutionLog::where('bot_id', $bot->id)
                    ->where('action', 'start')
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Failed to query trading_bot_execution_logs', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'last_signal_processed_at' => $lastSignalProcessed?->created_at?->toIso8601String(),
            'last_market_analysis_at' => $bot->last_market_analysis_at?->toIso8601String(),
            'signals_processed' => $signalsProcessed,
            'error_count_24h' => $errorCount,
            'worker_restart_count' => $restartCount,
        ];
    }

    /**
     * Get recent logs for a bot
     * 
     * @param int $botId
     * @param int $limit
     * @param string|null $level
     * @return array
     */
    public function getBotLogs(int $botId, int $limit = 50, ?string $level = null): array
    {
        // First, try dedicated bot log file (real-time worker logs)
        $botLogFile = storage_path("logs/trading-bot-{$botId}.log");
        $logFile = null;
        
        if (File::exists($botLogFile)) {
            $logFile = $botLogFile;
        } else {
            // Fallback to main Laravel log with bot_id filtering
            $logFile = storage_path('logs/laravel.log');
        }
        
        if (!File::exists($logFile)) {
            return [];
        }

        $logs = [];
        $lines = File::lines($logFile);
        $matchedLines = [];

        // Read file backwards (last N lines)
        $allLines = iterator_to_array($lines);
        $reversedLines = array_reverse($allLines);
        
        foreach ($reversedLines as $line) {
            // Skip lines that don't match Laravel log format
            // Laravel logs start with [YYYY-MM-DD HH:MM:SS]
            if (!preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
                // Skip non-Laravel format lines (PHP deprecations, etc.)
                continue;
            }
            
            // If using dedicated bot log file, include all Laravel-formatted lines
            // Otherwise, filter by bot_id in Laravel log
            if ($logFile === $botLogFile) {
                // Dedicated bot log - include all Laravel-formatted lines
                $includeLine = true;
            } else {
                // Laravel log - filter by bot_id
                $includeLine = strpos($line, "bot_id\":{$botId}") !== false || 
                              strpos($line, "'bot_id' => {$botId}") !== false ||
                              strpos($line, "bot_id\":\"{$botId}\"") !== false;
            }
            
            if (!$includeLine) {
                continue;
            }
            
            // Filter by level if specified
            if ($level) {
                $levelPattern = "local.{$level}";
                if (strpos($line, $levelPattern) === false && 
                    strpos($line, strtoupper($level)) === false &&
                    strpos($line, "[{$level}]") === false) {
                    continue;
                }
            }
            
            $matchedLines[] = $line;
            
            if (count($matchedLines) >= $limit) {
                break;
            }
        }

        // Reverse to get chronological order
        $matchedLines = array_reverse($matchedLines);

        // Parse log entries
        foreach ($matchedLines as $line) {
            $logs[] = [
                'raw' => $line,
                'timestamp' => $this->extractTimestamp($line),
                'level' => $this->extractLevel($line),
                'message' => $this->extractMessage($line),
            ];
        }

        return $logs;
    }

    /**
     * Get open positions for a bot
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function getOpenPositions(TradingBot $bot): array
    {
        // Check if trading_bot_positions table exists
        if (!Schema::hasTable('trading_bot_positions')) {
            return [];
        }

        try {
            $positions = TradingBotPosition::where('bot_id', $bot->id)
                ->where('status', 'open')
                ->with('executionPosition', 'signal')
                ->orderBy('created_at', 'desc')
                ->get();

            return $positions->map(function ($position) {
                $pnl = $position->profit_loss ?? 0;
                $pnlPercent = $position->getProfitLossPercentage();

                return [
                    'id' => $position->id,
                    'symbol' => $position->symbol,
                    'direction' => $position->direction,
                    'entry_price' => $position->entry_price,
                    'current_price' => $position->current_price,
                    'stop_loss' => $position->stop_loss,
                    'take_profit' => $position->take_profit,
                    'quantity' => $position->quantity,
                    'profit_loss' => $pnl,
                    'profit_loss_percent' => $pnlPercent,
                    'status' => $position->status,
                    'opened_at' => $position->opened_at?->toIso8601String(),
                    'signal_id' => $position->signal_id,
                    'signal_title' => $position->signal?->title,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to get open positions', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculate position statistics for a bot
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function calculatePositionStats(TradingBot $bot): array
    {
        // Check if trading_bot_positions table exists
        if (!Schema::hasTable('trading_bot_positions')) {
            return [
                'total_open' => 0,
                'total_unrealized_pnl' => 0,
                'positions_at_risk' => 0,
                'positions_near_tp' => 0,
                'closed_last_30d' => 0,
                'win_rate' => 0,
                'total_realized_pnl' => 0,
                'avg_hold_time_seconds' => null,
            ];
        }

        try {
            $openPositions = TradingBotPosition::where('bot_id', $bot->id)
                ->where('status', 'open')
                ->get();

            $totalUnrealizedPnL = $openPositions->sum('profit_loss') ?? 0;
            
            // Count positions at risk (near SL - within 1% of entry)
            $atRisk = 0;
            $nearTP = 0;
            
            foreach ($openPositions as $position) {
                if ($position->current_price && $position->entry_price && $position->stop_loss) {
                    $distanceToSL = abs($position->current_price - $position->stop_loss) / $position->entry_price * 100;
                    if ($distanceToSL < 1) {
                        $atRisk++;
                    }
                }
                
                if ($position->current_price && $position->entry_price && $position->take_profit) {
                    $distanceToTP = abs($position->current_price - $position->take_profit) / $position->entry_price * 100;
                    if ($distanceToTP < 1) {
                        $nearTP++;
                    }
                }
            }

            // Get closed positions stats (last 30 days)
            $closedPositions = TradingBotPosition::where('bot_id', $bot->id)
                ->where('status', 'closed')
                ->where('closed_at', '>=', now()->subDays(30))
                ->get();

            $winCount = $closedPositions->filter(fn($p) => ($p->profit_loss ?? 0) > 0)->count();
            $winRate = $closedPositions->count() > 0 ? ($winCount / $closedPositions->count()) * 100 : 0;
            $totalRealizedPnL = $closedPositions->sum('profit_loss') ?? 0;
            
            $avgHoldTime = null;
            if ($closedPositions->count() > 0) {
                $totalSeconds = $closedPositions->sum(function ($p) {
                    if ($p->opened_at && $p->closed_at) {
                        return $p->opened_at->diffInSeconds($p->closed_at);
                    }
                    return 0;
                });
                $avgHoldTime = $totalSeconds / $closedPositions->count();
            }

            return [
                'total_open' => $openPositions->count(),
                'total_unrealized_pnl' => $totalUnrealizedPnL,
                'positions_at_risk' => $atRisk,
                'positions_near_tp' => $nearTP,
                'closed_last_30d' => $closedPositions->count(),
                'win_rate' => round($winRate, 2),
                'total_realized_pnl' => $totalRealizedPnL,
                'avg_hold_time_seconds' => $avgHoldTime,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to calculate position stats', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
            return [
                'total_open' => 0,
                'total_unrealized_pnl' => 0,
                'positions_at_risk' => 0,
                'positions_near_tp' => 0,
                'closed_last_30d' => 0,
                'win_rate' => 0,
                'total_realized_pnl' => 0,
                'avg_hold_time_seconds' => null,
            ];
        }
    }

    /**
     * Get queue job statistics
     * 
     * @param int|null $botId
     * @return array
     */
    public function getQueueStats(?int $botId = null): array
    {
        $stats = [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'processing' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
        ];

        // Get job counts by type
        $jobTypes = [
            'ExecutionJob' => 0,
            'MonitorPositionsJob' => 0,
            'MonitorTradingBotWorkersJob' => 0,
            'FilterAnalysisJob' => 0,
        ];

        $jobs = DB::table('jobs')
            ->whereNull('reserved_at')
            ->get();

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? '';
            
            foreach (array_keys($jobTypes) as $type) {
                if (strpos($jobClass, $type) !== false) {
                    $jobTypes[$type]++;
                }
            }
        }

        $stats['by_type'] = $jobTypes;

        // Get recent ExecutionJob history (last 24 hours)
        $recentExecutions = DB::table('execution_logs')
            ->when($botId, function ($query) use ($botId) {
                // If bot_id is in execution_data, we'd need to parse JSON
                // For now, just get all recent executions
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $stats['executions_24h'] = $recentExecutions;

        return $stats;
    }

    /**
     * Extract timestamp from log line
     */
    protected function extractTimestamp(string $line): ?string
    {
        // Laravel log format: [2025-01-01 12:00:00]
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract log level from log line
     */
    protected function extractLevel(string $line): string
    {
        if (strpos($line, 'local.ERROR') !== false) return 'error';
        if (strpos($line, 'local.WARNING') !== false) return 'warning';
        if (strpos($line, 'local.INFO') !== false) return 'info';
        if (strpos($line, 'local.DEBUG') !== false) return 'debug';
        return 'unknown';
    }

    /**
     * Extract message from log line
     */
    protected function extractMessage(string $line): string
    {
        // Try to extract JSON message
        if (preg_match('/"message":"([^"]+)"/', $line, $matches)) {
            return $matches[1];
        }
        
        // Try to extract message from structured log format
        // Format: [timestamp] local.LEVEL: Message {"context"}
        if (preg_match('/\] local\.\w+:\s+(.+?)(?:\s+\{|\s*$)/', $line, $matches)) {
            return trim($matches[1]);
        }
        
        // Fallback: return last part of line after last ]
        $parts = explode(']', $line);
        $message = trim(end($parts));
        
        // Remove JSON context if present
        if (preg_match('/^(.+?)\s*\{.*\}$/', $message, $msgMatches)) {
            return trim($msgMatches[1]);
        }
        
        return $message;
    }

    /**
     * Get error count for bot in last N hours
     */
    protected function getErrorCount(int $botId, int $hours): int
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return 0;
        }

        $count = 0;
        $cutoffTime = now()->subHours($hours);
        
        $lines = File::lines($logFile);
        foreach ($lines as $line) {
            // Check if line contains bot_id and ERROR
            if (strpos($line, "bot_id\":{$botId}") !== false && 
                strpos($line, 'local.ERROR') !== false) {
                
                $timestamp = $this->extractTimestamp($line);
                if ($timestamp) {
                    try {
                        $logTime = \Carbon\Carbon::parse($timestamp);
                        if ($logTime->gte($cutoffTime)) {
                            $count++;
                        }
                    } catch (\Exception $e) {
                        // Ignore parse errors
                    }
                }
            }
        }

        return $count;
    }
}

